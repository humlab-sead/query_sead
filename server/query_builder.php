<?php

require_once __DIR__ . '/lib/dijkstra.php';
require_once __DIR__ . '/facet_config.php';

//***************************************************************************************************************************************************
/*
Function: get_range_selection_clauses
loop through selections and builds the sql for range selection, using lower and upper condition for column
Only design for a single interval  currently
*/
function get_range_selection_clauses($f_code, $skey, $current_selection_group)
{
    global $facet_definition;
    $current_selection_group = (array) $current_selection_group;
    $query_column = $facet_definition[$skey]["id_column"];
    foreach ($current_selection_group as $key2 => $current_selection) {
        if (isset($current_selection)) {
            foreach ($current_selection as $key5 => $selection_t) {
                // get lower and upper values and assign that according to the lower and upper type
                $selection_t = (array) $selection_t;
                $this_selection_a[$selection_t["selection_type"]] = $selection_t["selection_value"];
            }
            // if lower equals upper are the same value then it is better and safer to  use other procedures
            if ($this_selection_a["lower"] == $this_selection_a["upper"]) {
                $query_where.= " ( floor($query_column)=" . $this_selection_a["lower"] . "  ";
            } else {
                $query_where.= "($query_column>=" . $this_selection_a["lower"] . " and $query_column <=" . $this_selection_a["upper"] . "  ";
            }
            $or_include=" or $query_column is NULL";
            $or_include="";
            $query_where.= " $or_include  )  ";
        }
    }
    if ($facet_definition[$skey]["query_cond"] != "") {
        $query_where.="  and " . $facet_definition[$skey]["query_cond"] . "   ";
    }
    return $query_where;
}

//***************************************************************************************************************************************************
/*
function: get_discrete_selection_clauses
using globals facet_defintion and computes discete facet where conditions
parameters:
skey
current_selection_group
returns:
query_where parts
*/

function get_discrete_selection_clauses($f_code, $skey, $current_selection_group)
{
    global $facet_definition;
    $query_where = "";
    $query_column = $facet_definition[$skey]["id_column"];
    if (isset($current_selection_group) && ($f_code != $skey )) {
        foreach ($current_selection_group as $key2 => $sval) {
            // if there is a selection and the filter is not the target facets.
            $has_selection = false;
            $query_temp = "";
            foreach ($sval as $key5 => $selection) {
                $selection = (array) $selection;
                if (!empty($selection)) {
                    $has_selection = true;
                    $this_selection = $selection["selection_value"];
                    $query_temp2.= " '$this_selection'   ,";
                }
            }
            // strip last or
            if ($has_selection) {
                $query_temp2 = substr($query_temp2, 0, -2);
                $or_include= " or $query_column is null  ";
                $or_include= " ";
                $query_where = " ($query_column::text in (" . $query_temp2 . " )   $or_include )   ";
            }
        }
    }
    return $query_where;
}

class RouteReducer {

    private static function edgeExistsInRoutes($edge, $routes)
    {
        foreach ($routes as $route) {
            if (self::edgeExistsInRoute($edge, $route)) {
                return true;
            }
        }
        return false;
    }

    private static function edgeExistsInRoute($edge, $route)
    {
        foreach ($route as $compare_edge_key => $compare_edge) {
            if ($edge["from_table"] == $compare_edge["from_table"] && $edge["to_table"] == $compare_edge["to_table"]) {
                return true;
            }
        }
        return false;
    }

    private static function collectNewEdges($route, $reduced_routes)
    {
        // $reduce_route = array(); // new array for each loop check if edges exist, if not add them.
        foreach ($route as $edge_key => $edge) {
            if (!self::edgeExistsInRoutes($edge, $reduced_routes)) {
                $reduce_route[] = $edge;
            }
        }
        return $reduce_route;
    }

    public static function reduce($routes)
    {
        $reduced_routes[0] = $routes[array_keys($routes)[0]]; // add the first route to the reduced routes list
        foreach ($routes as $route) {
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
    private function make_sub_selects($routes, $edge_list, $subselect_where)
    {
        if (isset($routes)) {
            $route_counter = 0;
            foreach ($routes as $key => $route) {
                $sub_select[$route_counter] = " -- subselect $route_counter  \n";
                $filter_clause = "";
                foreach ($route as $edge_key => $edge) {
                    $join_type = " left ";
                    if (isset($subselect_where[$edge["to_table"]])) {
                        $filter_clause.=" and " . $subselect_where[$edge["to_table"]] . "\n";
                        $join_type = " inner ";
                    }
                    if (isset($subselect_where[$edge["from_table"]])) {
                        $filter_clause.=" and " . $subselect_where[$edge["from_table"]] . "\n";
                        $join_type = " inner ";
                    }
                    $sub_select[$route_counter].="  $join_type  join " . $edge["to_table"] . " \n";
                    $sub_select[$route_counter].=" on ";
                    foreach ($edge_list[$edge["to_table"]][$edge["from_table"]]["home_columns"] as $key_c1 => $home_column) {
                        $remote_column = $edge_list[$edge["to_table"]][$edge["from_table"]]["remote_columns"][$key_c1];
                        $sub_select[$route_counter].= $home_column . " = " . $remote_column . "\n";
                    }
                    if (isset($edge_list[$edge["to_table"]][$edge["from_table"]]["extra_condition"])) {
                        $filter_clause.=" and " . $edge_list[$edge["to_table"]][$edge["from_table"]]["extra_condition"];
                    }
                    $sub_select[$route_counter].= $filter_clause;
                }
                $route_counter++;
            }
        }
        return $sub_select;
    }

    private function  collectRoutes($lookup_list_tables, $numeric_edge_list, $table_list_outer, $table_list)
    {
        foreach ($table_list_outer as $start_table => $value1) {
            foreach ($table_list as $key2 => $value2) {
                if ($start_table != $key2) {
                    $destination_table = $key2;
                    $routes[] = $this->get_joins_information($lookup_list_tables, $numeric_edge_list, $start_table, $destination_table);
                }
            }
        }
        return $routes;
    }

    private function compileQueryJoins($routes, $subselect_where, $edge_list)
    {
        global $list_of_alias_tables;
        foreach ($routes as $key => $route) {
            $joins .= "";
            foreach ($route as $edge_key => $edge) {
                $source_table = $edge["from_table"];
                $target_table = $edge["to_table"];
                $edge_info    = $edge_list[$target_table][$source_table];

                $join_type = (isset($subselect_where[$target_table]) || isset($subselect_where[$source_table])) ? " inner " : " left ";

                $target_name = $list_of_alias_tables[$target_table] ?? $target_table;
                $alias_clause = ($target_name != $target_table) ? " as $target_table " : "";
                
                $join_criterias = [];
                foreach ($edge_info["home_columns"] as $key_c1 => $source_column) {
                    $target_column = $edge_info["remote_columns"][$key_c1];
                    $join_criterias[] = " $source_column = $target_column ";
                }

                $extra_condition = $edge_info["extra_condition"];
                if (isset($extra_condition)) {
                    $join_criterias[] = " $extra_condition ";
                }
                $join_criteria_clause = implode("\n  AND", $join_criterias);
                $joins .= " $join_type join $target_name $alias_clause\n  on $join_criteria_clause ";
            }
        }
        return $joins;
    }

    public function get_query_information($edge_list, $numeric_edge_list, $lookup_list_tables, $facet_definition, $facetConfig, $f_code, $extra_tables, $activeFacets)
    {
        global $list_of_alias_tables;
        $query = array();
        $table_list = array();
        $facet_selections = FacetConfig::getItemGroupsSelectedByUser($facetConfig);
        if (isset($facet_definition[$f_code]["query_cond_table"]) && !empty($facet_definition[$f_code]["query_cond_table"])) {
            foreach ($facet_definition[$f_code]["query_cond_table"] as $cond_key => $cond_table) {
                $extra_tables[] = $cond_table;
            }
        }
        
        $query_column = $query_where = "";
        
        if (isset($activeFacets)) {

            // list must exist, ie there must be some filters in order build a query

            $facet_positions = array_flip($activeFacets);

            foreach ($activeFacets as $pos => $facetCode) {

                if (!isset($facet_selections[$facetCode])) {
                    continue;
                }
                
                while (list($skey1, $selection_group) = each($facet_selections[$facetCode])) {

                    $affects_query = ($facet_positions[$f_code] > $facet_positions[$facetCode] ||
                        ($facet_definition[$f_code]["facet_type"] == "range" && $facet_positions[$f_code] == $facet_positions[$facetCode]) ||
                        ($facet_definition[$f_code]["facet_type"] == "geo" && $facet_positions[$f_code] == $facet_positions[$facetCode]));

                    if (!$affects_query) {
                        continue;
                    }
                    
                    $table_with_selections = isset($facet_definition[$facetCode]["alias_table"]) ? $facet_definition[$facetCode]["alias_table"] : $facet_definition[$facetCode]["table"] ;
                    
                    switch ($facet_definition[$facetCode]["facet_type"]) {
                        case "range":
                            $query_where.=get_range_selection_clauses($f_code, $facetCode, $selection_group)."      AND ";
                            if (!empty($facet_definition[$facetCode]["query_cond_table"])) {
                                foreach ($facet_definition[$facetCode]["query_cond_table"] as $cond_table) {
                                    $table_list[$cond_table] = true;
                            }
                        }
                        $query_where_list[$table_with_selections][]=get_range_selection_clauses($f_code, $facetCode, $selection_group);
                        $subselect_where[$table_with_selections]=get_range_selection_clauses($f_code, $facetCode, $selection_group). "   AND ";
                        break;
                        case "discrete":
                            if (!empty($selection_group)) {
                                $query_where.= get_discrete_selection_clauses($f_code, $facetCode, $selection_group). "     AND ";
                                if (!empty($facet_definition[$facetCode]["query_cond_table"])) {
                                    foreach ($facet_definition[$facetCode]["query_cond_table"] as $cond_table) {
                                        $table_list[$cond_table] = true;
                                }
                            }
                            $query_where_list[$table_with_selections][]=get_discrete_selection_clauses($f_code, $facetCode, $selection_group);
                            $subselect_where[$table_with_selections]=get_discrete_selection_clauses($f_code, $facetCode, $selection_group). "   AND ";
                        }
                        break;
                        case "geo":
                            $query_where.=get_geo_filter_clauses($f_code, $facetCode, $selection_group). "     AND " ;
                            $query_where_list[$table_with_selections][]=get_geo_filter_clauses($f_code, $facetCode, $selection_group);
                            $subselect_where[$table_with_selections]=get_geo_filter_clauses($f_code, $facetCode, $selection_group). "  AND ";
                            break;
                    }
                    
                    $table_list[$table_with_selections] = true; // set table to use to true, is used later when picking the graph
                    $subselect_where[$table_with_selections] = substr($subselect_where[$table_with_selections], 0, - 5); //remove last AND
                }
            }
        }

        if (!empty($extra_tables)) {
            foreach ($extra_tables as $value) {
                $table_list[$value] = true; // data table is added to the list of tables makes unique
            }
        }
        $query_where = substr($query_where, 0, strlen($query_where) - 5); //remove last AND
        if (isset($query_where_list)) {
            foreach ($query_where_list as $table_with_criteria => $sql_criteria_list) {
                $new_query_where.="( ".implode(" AND ", $sql_criteria_list). " )   -- Make nice \n   AND ";
            }
        }
        $query_where = substr($new_query_where, 0, -5); //remove last AND

        $query["where"] = $query_where . "";

        $query_cond = $facet_definition[$f_code]["query_cond"];
        if (!empty($query_cond)) {
            $query["where"] .= ($query["where"] == "" ? "   " : "  and ") . $query_cond;
        }

        $query["select"] = $facet_definition[$f_code]["id_column"] . "," .$facet_definition[$f_code]["name_column"];

        $current_table = $facet_definition[$f_code]["table"];
        $alias_table = $facet_definition[$f_code]["alias_table"];
        $alias_clause = isset($alias_table) ? " as $alias_table" : "";

        $query["tables"] = "$current_table $alias_clause";

        // Join clauses between tables
        // adds extra tables from argument
        // Join clauses between tables and tables names

        $alias_table = $alias_table ?? $current_table;
        $start_table = $alias_table;
        $table_list_outer[$start_table] = true;
        $table_list[$start_table] = true;

        $routes = $this->collectRoutes($lookup_list_tables, $numeric_edge_list, $table_list_outer, $table_list);

        $sub_selects = $this->make_sub_selects($routes, $edge_list, $subselect_where) ;

        if (isset($routes)) {
            $reduced_routes = RouteReducer::reduce($routes);
            $query["joins"] = $this->compileQueryJoins($reduced_routes, $subselect_where, $edge_list);
        }

        $query["none_reduced_routes"] = $routes;
        $query["reduced_routes"] = $reduced_routes;
        $query["sub_selects"] = $sub_selects;

        return $query;
    }

    public function get_joins_information($f_tables, $numeric_edge_list, $start_table, $destination_table)
    {
        if (!(isset($f_tables[$start_table]) && isset($f_tables[$destination_table]))) {
            echo "Tables $start_table or $destination_table does not exists in the graph check configuration";
            exit;
        }
        
        $start_node = $f_tables[$start_table];
        $dijkstra = new Dijkstra($numeric_edge_list, I, $matrixWidth);
        
        $destination_node = $f_tables[$destination_table];
        $dijkstra->findShortestPath($start_node);
        $dijstra_result = $dijkstra->getResultsAsArray($destination_node);
        
        if (count($dijstra_result) > 0) {
            $keys=array_keys($dijstra_result);
            $first_key = $keys[0];
            $route = $dijstra_result[$first_key]; // first element
            $router_ordinal=array_values($route);
            
            for ($count = 0; $count <= count($route) - 2; $count++) {
                $edge_list[$count] = array(
                    "from_table" => array_search($router_ordinal[$count], $f_tables),
                    "to_table" => array_search($router_ordinal[$count + 1], $f_tables)
                );
            }
        }
        return $edge_list;
    }

}

class QueryBuildService {

    //***************************************************************************************************************************************************
    /*
    Function: compileQuery
    This the core of the dynamic query builder.     It's input are previous selected filters and the code of the facet that triggered the action.
    It is also use an array of filter to filter by text each facet result,
    Parameters:
    * $facetConfig all params, selections, text_filter, positions of facets ie the view.state of the client)
    * the target facet to which the query should populate/compute counts etc
    * $data_tables, any extra tables that should be part of the query, the function uses the tables via get_joins to join the tables
    * $activeFacets, the list of the facets in the view-state
    Logics:
    *  Get all selection preceding the target facet.
    *  Make query-where statements depending on which type of facets (range or discrete)
    Exceptions:
    * a - for target facets (f_code) of "discrete" type it should be affected by all selection from facets preceeding the requested/target facets.
    * b - for "range" facet it should also include the condition of the range-facets itself, although the bound should be expanded to show values outside the limiting range.

    Returns:
    select clauses (not used)
    where clauses
    join conditions
    tables to used
    */
    public static function compileQuery($facetConfig, $facetCode, $extra_tables, $activeFacets)
    {
        global $facet_definition, $join_columns, $f_tables, $ourMap;
        $query_builder = new QueryBuilder();
        $query_info = $query_builder->get_query_information($join_columns, $ourMap, $f_tables, $facet_definition, $facetConfig, $facetCode, $extra_tables, $activeFacets);
        return $query_info;
    }
}
?>