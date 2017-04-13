<?php

require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/lib/dijkstra.php';
require_once __DIR__ . '/lib/utility.php';
require_once __DIR__ . '/facet_config.php';

class FacetSelectionCompiler {

    public function compile($targetCode, $currentCode, $picks)
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

    public function compile($targetCode, $currentCode, $picks)
    {
        $facet = FacetRegistry::getDefinition($currentCode);
        $range = $this->getTypePickValues($picks, ["lower" => 0, "upper" => 0]);
        if ($range['lower'] == $range['upper']) {                                                 // Safer to do it this way if equal
            $criteria = " (floor({$facet->id_column}) = {$range['lower']})"; 
        } else {
            $criteria = " ({$facet->id_column} >= {$range['lower']} and {$facet->id_column} <= {$range['upper']})";
        }
        $criteria .= str_prefix(" and ", $facet->query_cond);
        return $criteria;
    }

    public function getTypePickValues($picks, $default=[])
    {
        $values = $default; 
        array_walk($picks, function ($x) use ($values) { $values[$x->type] = $x->value; });
        return $values;
    }

    public function is_affected_position($targetPosition, $currentPosition)
    {
        return parent::is_affected_position($targetPosition, $currentPosition) || ($targetPosition == $currentPosition);
    }
}

class DiscreteFacetSelectionCompiler extends FacetSelectionCompiler {

    public function compile($targetCode, $currentCode, $picks)
    {
        if ($targetCode == $currentCode || count($picks) == 0) {
            return "";
        }
        $facet = FacetRegistry::getDefinition($currentCode);
        $values = array_map(function ($x) { return "'{$x->value}'"; }, $picks);
        $criteria = " ({$facet->id_column}::text in (" . implode(', ', $values) . ")) ";
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

    public function get_query_information($facetsConfig, $facetCode, $extra_tables, $facetCodes)
    {
        $target_facet = FacetRegistry::getDefinition($facetCode);

        $unique_tables = []; 
        array_add_unique($unique_tables, $extra_tables, $target_facet->query_cond_table);

        $filter_clauses = [];
        $facetCodes = $facetsConfig == NULL ? [] : ($facetCodes ?? []);
        $facet_positions = array_flip($facetCodes);

        foreach ($facetCodes as $currentCode) {
            $config = $facetsConfig->getConfig($currentCode);
            if (count($config->picks) == 0) {
                continue;
            }
            $compiler = FacetSelectionCompiler::getCompiler($config->facet->facet_type);
            if (!$compiler->is_affected_position($facet_positions[$facetCode], $facet_positions[$currentCode])) {
                continue;
            }
            $filter_clause = $compiler->compile($facetCode, $currentCode, $config->picks);
            $filter_clauses[$config->facet->table_or_alias][] = $filter_clause; 
            array_add_unique($unique_tables, $config->facet->query_cond_table, $config->facet->table_or_alias);
        }

        $join_criterias = array_map(function($x) { return implode(" AND ", $x); }, $filter_clauses);

        $routes = $this->computeShortestJoinPaths($target_facet->table_or_alias, $unique_tables);
        $reduced_routes = RouteReducer::reduce($routes ?? []);

        $join_clause = $this->compileQueryJoins($reduced_routes ?? [], $join_criterias);

        $querySetup = new QuerySetup($target_facet, $join_clause, $filter_clauses, $routes, $reduced_routes);

        return $querySetup;
    }

    private function computeShortestJoinPaths($start_table, $destination_tables)
    {
        $routes = [];
        array_add_unique($destination_tables, $start_table);
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

class QuerySetup
{
    public $facet;
    public $fields = [];

    public $sql_fields;
    public $sql_table;
    public $sql_where;
    public $sql_where2;
    public $sql_joins;

    public $none_reduced_routes;
    public $reduced_routes;

    // FIXME: remove
    public $select_fields;
    public $tables;
    public $where;
    public $joins; 

    function __construct($facet, $sql_joins, $sql_filter_clauses, $none_reduced_routes, $reduced_routes) {

        $this->facet = $facet;
        $this->fields = [ $facet->id_column, $facet->name_column ];
        $this->sql_fields = implode(", ", $this->fields);
        $this->sql_table = $facet->table . " " . str_prefix("AS", $facet->alias_table, " ");
        $this->sql_where = $this->generateWhereClause($facet, $sql_filter_clauses);
        $this->sql_where2 = str_prefix("AND ", $this->sql_where);
        $this->sql_joins = $sql_joins;

        $this->none_reduced_routes = $none_reduced_routes;
        $this->reduced_routes = $reduced_routes;
        
        // FIXME: remove
        $this->select_fields = $this->sql_fields;
        $this->tables = $this->sql_table;
        $this->where = $this->sql_where;
        $this->joins = $this->sql_joins_clause;
    }

    private function generateWhereClause($facet, $filter_clauses)
    {
        $sql_where_clauses = array_map(function($x) { return "(" . implode(" AND ", $x). ")\n"; }, $filter_clauses);
        if (!empty($facet->query_cond)) {
            $sql_where_clauses[] = $facet->query_cond;
        }
        $sql_where = implode(" AND ", $sql_where_clauses);
        return $sql_where;
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
    * $facetsConfig     The current client facet view state (all params, selections, text_filter, positions of facets etc)
    * $facetCode        The target facet that the query populates/computes counts etc for.
    * $extra_tables     Any extra tables that should be part of the query, the function uses the tables via get_joins to join the tables
    * $facetCodes   The list of the facets (facet codes) currently active in the client view
    Logics:
    *  Get all selection preceding the target facet.
    *  Make query-where statements depending on which type of facets (range or discrete)
    Exceptions:
    * a - Discrete facets should be filtered by all selection from preceeding (but not including) the target facet.
    * b - Range facets should also be filtered by range-facets itself, although the bound should be expanded to show values outside the limiting range.
    Returns:
        Query object with SQL-parts
    */
    public static function compileQuery($facetsConfig, $facetCode, $extraTables, $facetCodes)
    {
        global $weightedGraph;
        $query_builder = new QueryBuilder($weightedGraph);
        $querySetup = $query_builder->get_query_information($facetsConfig, $facetCode, $extraTables, $facetCodes);
        return $querySetup;
    }

    public static function compileQuery2($facetsConfig, $facetCode, $extraTables = [])
    {
        $facetCodes = $facetsConfig->getFacetCodes();
        if (!in_array($facetCode, $facetCodes)) {
            $facetCodes[] = $facetCode;
        }
        $querySetup = self::compileQuery($facetsConfig, $facetCode, $extraTables, $facetCodes);
        return $querySetup;
    }
}
?>