<?php

require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/lib/dijkstra.php';
require_once __DIR__ . '/lib/utility.php';
require_once __DIR__ . '/facet_config.php';

class FacetSelectionCompiler {

    public function compile($targetCode, $currentCode, $groups)
    {
        return NULL;
    }

    public function is_affected_position($targetPosition, $currentPosition)
    {
        return $targetPosition > $currentPosition;
    }

    protected static $compilers = NULL;
    public static function getCompiler($type)
    {
        if (self::$compilers == NULL)
           self::$compilers = [
                "discrete" => new DiscreteFacetSelectionCompiler(),
                "range" => new RangeFacetSelectionCompiler(),
                "null" => new FacetSelectionCompiler()
            ];
        return array_key_exists($type, self::$compilers) ? self::$compilers[$type] : self::$compilers["null"];
    }
}

class RangeFacetSelectionCompiler extends FacetSelectionCompiler {

    //***************************************************************************************************************************************************
    /*
    Function: RangeFacetSelectionCompiler
    loop through selections and builds the sql for range selection, using lower and upper condition for column
    Only design for a single interval  currently
    */

    function add_prefix($prefix, $value, $glue=" ", $space="")
    {
        return !empty($value) ? "$space$prefix$glue$value$space" : $value;
    }

    public function compile($targetCode, $currentCode, $groups)
    {
        $facet = FacetRegistry::getDefinition($currentCode);
        $query_column =$facet["id_column"];
        $query_cond = $facet["query_cond"];
        $criteria = "";
        foreach ($groups as $group) {
            if (!isset($group)) {
                continue;
            }
            $range = ["lower" => 0, "upper" => 0];
            foreach ($group as $item) {
                $item = (array) $item;
                $range[$item["selection_type"]] = $item["selection_value"];
            }
            $lower = $range["lower"];
            $upper = $range["upper"];
            if ($lower == $upper) {                                                 // Safer to do it this way if equal
                $criteria .= " (floor($query_column) = $lower)"; 
            } else {
                $criteria .= " ($query_column >= $lower and $query_column <= $upper)";
            }
        }
        $criteria .= str_prefix(" and ", $query_cond);
        return $criteria;
    }

    public function is_affected_position($targetPosition, $currentPosition)
    {
        return parent::is_affected_position($targetPosition, $currentPosition) || ($targetPosition == $currentPosition);
    }
}

class DiscreteFacetSelectionCompiler extends FacetSelectionCompiler {

    //***************************************************************************************************************************************************
    /*
    class: DiscreteFacetSelectionCompiler
    Compiles discrete facet where conditions
    parameters:
    skey
    current_selection_group
    returns:
    query criteria parts for user selections
    */

    public function compile($targetCode, $currentCode, $group)
    {
        if (empty($group) || ($targetCode == $currentCode)) {
            return NULL;
        }
        $facet = FacetRegistry::getDefinition($currentCode);
        $query_column = $facet['id_column'];
        $criteria = "";
        // there is a selection and the filter is not the target facets.
        foreach ($group as $items) {
            $values = [];
            foreach ($items as $item) {
                $item = (array)$item;
                if (!empty($item)) {
                    $values[] = "'{$item["selection_value"]}'";
                }
            }
            if (count($values) > 0) {
                // FIXME: Shouldnt' this be concatenation? and 'and'' between???
                $criteria = " ($query_column::text in (" . implode(', ', $values) . ")) ";
            }
        }
        return $criteria;
    }
}

class RouteReducer {

    private static function edgeExistsInRoutes($edge, $routes)
    {
        foreach ($routes as $route) {
            if (self::edgeExistsInRoute($edge, $route))
                return true;
        }
        return false;
    }

    private static function edgeExistsInRoute($edge, $route)
    {
        foreach ($route as $compare_edge) {
            if ($edge["from_table"] == $compare_edge["from_table"] && $edge["to_table"] == $compare_edge["to_table"])
                return true;
        }
        return false;
    }

    private static function collectNewEdges($route, $reduced_routes)
    {
        $reduced_route = [];
        foreach ($route as $edge) {
            if (!self::edgeExistsInRoutes($edge, $reduced_routes)) {
                $reduced_route[] = $edge;
            }
        }
        return $reduced_route;
    }

    public static function reduce($routes)
    {
        $reduced_routes = [];
        foreach ($routes ?? [] as $route) {
            $reduced_route = self::collectNewEdges($route, $reduced_routes);
            if (!empty($reduced_route) && count($reduced_route) > 0) {
                $reduced_routes[] = $reduced_route;
            }
        }
        return $reduced_routes;
    }
}

class QueryBuilder
{
    public $weightedGraph;
    public $joinColumns;
    public $edges;
    public $tableIds;
    public $aliasTables;

    function __construct($graph){
        $this->weightedGraph = $graph;
        $this->joinColumns = $graph->joinColumns;
        $this->edges = $graph->edges;
        $this->tableIds = $graph->tableIds;
        $this->aliasTables = $graph->aliasTables;
    }

    private function compileQueryJoins($routes, $subselect_where)
    {
        $joins = "";
        foreach ($routes ?? [] as $route) {
            foreach ($route as $edge) {
                $source_table = $edge["from_table"];
                $target_table = $edge["to_table"];
                $relation = $this->joinColumns[$target_table][$source_table];

                $join_type = (isset($subselect_where[$target_table]) || isset($subselect_where[$source_table])) ? "inner" : "left";

                $target_name = $this->aliasTables[$target_table] ?? $target_table;
                $alias_clause = ($target_name != $target_table) ? " as $target_table " : "";
                
                // FIXME Check if reset should for each route, not each edge, as in compileJoins above
                $join_criterias = [];
                foreach ($relation["home_columns"] as $key_c1 => $source_column) {
                    $target_column = $relation["remote_columns"][$key_c1];
                    $join_criterias[] = " $source_column = $target_column ";
                }

                $extra_condition = $relation["extra_condition"];
                if (isset($extra_condition)) {
                    $join_criterias[] = " $extra_condition ";
                }
                $join_criteria_clause = implode("\n  and", $join_criterias);
                $joins .= "  $join_type join $target_name $alias_clause\n  on $join_criteria_clause \n";
            }
        }
        return $joins;
    }

    public function get_query_information($facetConfig, $facetCode, $extra_tables, $activeFacets)
    {
        $unique_tables = [];   // Unique list of tables involved in join

        $facet_selections = FacetConfig::getUserPickGroups($facetConfig);
        $target_facet = FacetRegistry::getDefinition($facetCode);

        array_add_unique($unique_tables, $extra_tables);
        array_add_unique($unique_tables, $target_facet["query_cond_table"]);

        $query_where_list = [];
        $activeFacets = $activeFacets ?? [];
        $facet_positions = array_flip($activeFacets);
        $subselect_where = [];
        foreach ($activeFacets as $currentCode) {

            if (!isset($facet_selections[$currentCode])) {
                continue;
            }
            $current_facet = FacetRegistry::getDefinition($currentCode);
            $current_table = $current_facet["alias_table"] ?? $current_facet["table"];

            $compiler = FacetSelectionCompiler::getCompiler($current_facet["facet_type"]);
            if (!$compiler->is_affected_position($facet_positions[$facetCode], $facet_positions[$currentCode])) {
                continue;
            }

            while (list($x, $selection_group) = each($facet_selections[$currentCode])) {
                $clause = $compiler->compile($facetCode, $currentCode, $selection_group);
                if (!empty($clause)) {
                    $query_where_list[$current_table][] = $clause;
                }
                array_add_unique($unique_tables, $current_facet["query_cond_table"]);
                array_add_unique($unique_tables, $current_table);
            }
            $subselect_where[$current_table] = implode(" and ", $query_where_list[$current_table]);
        }

        // FIXME:  $subselect_where and $criteria_clauses are the same at this point???
        $criteria_clauses = array_map(function($x) { return "(" . implode(" and ", $x). ")\n"; }, $query_where_list);
        if (!empty($current_facet["query_cond"])) {
           $criteria_clauses[] = $current_facet["query_cond"];
        }
        $where_clause = implode(" and ", $criteria_clauses);
        $select_fields = $target_facet['id_column'] . "," .$target_facet['name_column'];
        $target_table = $target_facet['table'];
        $alias_table = $target_facet["alias_table"];
        $table_clause = "$target_table " . (isset($alias_table) ? " as $alias_table" : "");
        $start_table = $alias_table ?? $target_facet["table"];

        array_add_unique($unique_tables, $start_table);

        $routes = $this->computeShortestJoinPaths($start_table, $unique_tables);
        $reduced_routes = RouteReducer::reduce($routes ?? []);
        $join_clause = $this->compileQueryJoins($reduced_routes ?? [], $subselect_where);

        $query = [
            "select" => $select_fields,
            "tables" => $table_clause,
            "joins" => $join_clause,
            "where" => $where_clause,
            "none_reduced_routes" => $routes,
            "reduced_routes" => $reduced_routes
        ];
        return $query;
    }

    private function computeShortestJoinPaths($start_table, $destination_tables)
    {
        $routes = [];
        foreach ($destination_tables as $destination_table) {
            if ($start_table != $destination_table) {
                $routes[] = $this->computeShortestJoinPath($start_table, $destination_table);
            }
        }
        return $routes;
    }

    public function computeShortestJoinPath($start_table, $destination_table)
    {
        if (!(isset($this->tableIds[$start_table]) && isset($this->tableIds[$destination_table]))) {
            throw new Exception("Tables $start_table or $destination_table does not exists in the graph check configuration");
        }
        $start_node = $this->tableIds[$start_table];
        $dijkstra = new Dijkstra($this->edges, I);
        $destination_node = $this->tableIds[$destination_table];
        $dijkstra->findShortestPath($start_node);
        $dijstra_result = $dijkstra->getResultsAsArray($destination_node) ?? [];
        if (empty($dijstra_result)) {
            return NULL;
        }
        $route = array_values(reset($dijstra_result));      // Take values from first route in result
        $result = [];
        for ($i = 0; $i < count($route) - 1; $i++) {
            $from_table = array_search($route[$i], $this->tableIds);
            $to_table = array_search($route[$i + 1], $this->tableIds);
            $result[$i] = [ "from_table" => $from_table, "to_table" => $to_table ];
        }
        return $result;
    }
}

class QueryBuildService {

    //***************************************************************************************************************************************************
    /*
    Function: compileQuery - the core of the dynamic query builder.

    It's input are:
      1) the facet (code) that triggered the action
      2) the user selected filters preceeding the triggering facet

    It also uses an array of filters that are used to filter each facet result by text

    Parameters:
    * $facetConfig   The current client facet view state (all params, selections, text_filter, positions of facets etc)
    * $facetCode     The target facet that the query populates/computes counts etc for.
    * $extra_tables  Any extra tables that should be part of the query, the function uses the tables via get_joins to join the tables
    * $activeFacets  The list of the facets (facet codes) currently active in the client view
    Logics:
    *  Get all selection preceding the target facet.
    *  Make query-where statements depending on which type of facets (range or discrete)
    Exceptions:
    * a - for target facets (f_code) of "discrete" type it should be affected by all selection from facets preceeding the requested/target facets.
    * b - for "range" facet it should also include the condition of the range-facets itself, although the bound should be expanded to show values outside the limiting range.
    Returns:
        Query object with SQL-parts
    */
    public static function compileQuery($facetConfig, $facetCode, $extra_tables, $activeFacets)
    {
        global $weightedGraph;
        $query_builder = new QueryBuilder($weightedGraph);
        $query = $query_builder->get_query_information($facetConfig, $facetCode, $extra_tables, $activeFacets);
        return $query;
    }
}
?>