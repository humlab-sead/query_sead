<?php


//***************************************************************************************************************************************************
/*
Function: get_joins
function to compute the join condition between two tables

Parameters:
start_table - start table
destination_table - destination table
join_columns - Using globally defined information about the abstacted table structure. join_columns stores the join condition on edge of a graph describing the table.

Returns:
Multidimensional associative array
sql_str: where condition to make the join
tables_array:tables to be used where the key is the table to use.
sql_list: list of sql statement to make the joins

see also:
http://www.springerlink.com/content/0285j5422t0x17k4/

*/

// function get_joins($start_table, $destination_table)
// {
//     global $join_columns;
//     global $f_tables;
//     global $ourMap;
//     global $matrixWidth;
    
//     // check if tables exists in the graph.
//     if (!(isset($f_tables[$start_table]) && isset($f_tables[$destination_table]))) {
//         echo "Tables $start_table or $destination_table does not exists in the graph check configueration";
//         exit;
//     }
    
//     $start_node = $f_tables[$start_table];
    
//     $dijkstra = new Dijkstra($ourMap, I, $matrixWidth);
//     $destination_node = $f_tables[$destination_table];
//     $dijkstra->findShortestPath($start_node);
//     $dijstra_result = $dijkstra->getResultsAsArray($destination_node);
//     $sql_str = "";
//     foreach ($dijstra_result as $value) {
//         $count = 0;
//         foreach ($value as $element) {
//             // below a graph is used to find the columns for joing the tables following the shortest way from pair of tables
//             // start after the first item have been reach
//             if ($count > 0) {
//                 $table1 = array_search($prev_value, $f_tables);
//                 $table2 = array_search($element, $f_tables);
//                 $remote_columns = $join_columns[$table1][$table2]["remote_columns"];
//                 $home_columns = $join_columns[$table1][$table2]["home_columns"];
//                 if (!isset($join_list_spec[$table1][$table2]) && !isset($join_list_spec[$table2][$table1])) {
//                     $join_list_spec[$table1][$table2] = $join_columns[$table1][$table2]["join_condition"];
//                 }
                
//                 $counter = 0;
//                 while ($counter < count($remote_columns) && $counter < count($home_columns)) {
//                     $sql_str.= $home_columns[$counter] . " = " . $remote_columns[$counter] . " and \n ";
//                     $sql_list[$home_columns[$counter]][$remote_columns[$counter]] = true;
//                     $connected_tables[$table1][$table2] = true;
//                     $counter++;
//                 }
                
//                 if (!empty($join_columns[$table1][$table2]["extra_condition"])) {
//                     $sql_str.=$join_columns[$table1][$table2]["extra_condition"] . " AND \n ";
//                     $extra_conditions.=$join_columns[$table1][$table2]["extra_condition"] . "         AND \n";
//                 }
//             }
            
//             $table_joins[array_search($element, $f_tables)] = true; // add tables in the path to be used in queries
//             $prev_value = $element;
//             $count++;
//         }
//     }
//     $result_obj["extra_conditions"] = $extra_conditions;
//     $result_obj["sql_str"] = substr($sql_str, 0, strlen($sql_str) - 7); // remove trailing " AND "
//     // Return/build the array that holds the tables needed for the joining.
//     foreach ($table_joins as $key => $values) {
//         $result_obj["tables_array"][$key] = true;
//     }
    
//     $result_obj["sql_list"] = $sql_list;
//     $result_obj["join_list"] = $join_list_spec;
//     $result_obj["connected_tables"] = $connected_tables;
//     return $result_obj;
// }

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

class QueryBuilder
{
    public function make_sub_selects($routes, $edge_list, $subselect_where)
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
    
    public function get_query_information($edge_list, $numeric_edge_list, $lookup_list_tables, $facet_definition, $facetConfig, $f_code, $extra_tables, $f_list)
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
        
        if (isset($f_list)) {

            // list must exist, ie there must be some filters in order build a query

            $facet_positions = array_flip($f_list);

            foreach ($f_list as $pos => $facet) {

                if (!isset($facet_selections[$facet])) {
                    continue;
                }
                
                while (list($skey1, $selection_group) = each($facet_selections[$facet])) {

                    // tricky condition here!2009-11-28
                    $affects_query = ($facet_positions[$f_code] > $facet_positions[$facet] ||
                        ($facet_definition[$f_code]["facet_type"] == "range" && $facet_positions[$f_code] == $facet_positions[$facet]) ||
                        ($facet_definition[$f_code]["facet_type"] == "geo" && $facet_positions[$f_code] == $facet_positions[$facet]));

                    if (!$affects_query) {
                        continue;
                    }
                    
                    $table_with_selections = isset($facet_definition[$facet]["alias_table"]) ? $facet_definition[$facet]["alias_table"] : $facet_definition[$facet]["table"] ;
                    
                    switch ($facet_definition[$facet]["facet_type"]) {
                        case "range":
                            $query_where.=get_range_selection_clauses($f_code, $facet, $selection_group)."      AND ";
                            if (!empty($facet_definition[$facet]["query_cond_table"])) {
                                foreach ($facet_definition[$facet]["query_cond_table"] as $cond_table) {
                                    $table_list[$cond_table] = true;
                            }
                        }
                        $query_where_list[$table_with_selections][]=get_range_selection_clauses($f_code, $facet, $selection_group);
                        $subselect_where[$table_with_selections]=get_range_selection_clauses($f_code, $facet, $selection_group). "   AND ";
                        break;
                        case "discrete":
                            if (!empty($selection_group)) {
                                $query_where.= get_discrete_selection_clauses($f_code, $facet, $selection_group). "     AND ";
                                if (!empty($facet_definition[$facet]["query_cond_table"])) {
                                    foreach ($facet_definition[$facet]["query_cond_table"] as $cond_table) {
                                        $table_list[$cond_table] = true;
                                }
                            }
                            $query_where_list[$table_with_selections][]=get_discrete_selection_clauses($f_code, $facet, $selection_group);
                            $subselect_where[$table_with_selections]=get_discrete_selection_clauses($f_code, $facet, $selection_group). "   AND ";
                        }
                        break;
                        case "geo":
                            $query_where.=get_geo_filter_clauses($f_code, $facet, $selection_group). "     AND " ;
                            $query_where_list[$table_with_selections][]=get_geo_filter_clauses($f_code, $facet, $selection_group);
                            $subselect_where[$table_with_selections]=get_geo_filter_clauses($f_code, $facet, $selection_group). "  AND ";
                            break;
                    }
                    
                    $table_list[$table_with_selections] = true; // set table to use to true, is used later when picking the graph
                    $subselect_where[$table_with_selections] = substr($subselect_where[$table_with_selections], 0, - 5); //remove last AND
                }
            }
        }
        // array_flip
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

        // put the where condition to the return object
        $query["where"] = $query_where . "";
        if (!empty($facet_definition[$f_code]["query_cond"])) {
            if ($query["where"] == "") {
                $query["where"].="   " . $facet_definition[$f_code]["query_cond"];
            } else {
                $query["where"].="  and " . $facet_definition[$f_code]["query_cond"];
            }
        }

        $query["select"] = $facet_definition[$f_code]["id_column"].",".$facet_definition[$f_code]["name_column"];


        $current_table=  isset($facet_definition[$f_code]["alias_table"]) ?  $facet_definition[$f_code]["table"]. " as ".$facet_definition[$f_code]["alias_table"] : $facet_definition[$f_code]["table"] ;
        $query["tables"] =  $current_table;

        // Join clauses between tables
        // adds extra tables from argument
        // Join clauses between tables and tables names

        $counter = 0;
        $alias_table=  isset($facet_definition[$f_code]["alias_table"]) ?  $facet_definition[$f_code]["alias_table"] : $facet_definition[$f_code]["table"] ;
        $start_table =  $alias_table;
        $table_list_outer[$start_table]=true;
        $table_list[$start_table]=true;
        foreach ($table_list_outer as $start_table => $value1) {
            foreach ($table_list as $key2 => $value2) {
                if ($start_table != $key2) {
                    $destination_table = $key2;
                    $routes[] = $this->get_joins_information($lookup_list_tables, $numeric_edge_list, $start_table, $destination_table);
                }
            }
        }

        $none_reduced_routes=$routes;
        $sub_selects=$this->make_sub_selects($routes, $edge_list, $subselect_where) ;

        if (isset($routes)) {
            $routes = $this->route_reducer($routes);
            $route_counter = 0;
            foreach ($routes as $key => $route) {
                $query["joins"].="";//-- Reduced route # $route_counter \n";
                foreach ($route as $edge_key => $edge) {
                    $join_type=" left ";
                    if (isset($subselect_where[$edge["to_table"]])) {
                        $filter_clause.=" and " . $subselect_where[$edge["to_table"]]. "\n";
                        $join_type=" inner ";
                    }
                    
                    if (isset($subselect_where[$edge["from_table"]])) {
                        $filter_clause.=" and " . $subselect_where[$edge["from_table"]]. "\n";
                        $join_type=" inner ";
                    }
                    
                    // check if the table in route is the start or destination table
                    // then use the correct alias...
                    if (isset($list_of_alias_tables[$edge["to_table"]])) {
                        $table_to_be_joined=$list_of_alias_tables[$edge["to_table"]] ;//. " as " . $edge["to_table"];
                        $alias_to_be_used=$edge["to_table"];// ."_".$route_counter;
                    } else {
                        $table_to_be_joined= $edge["to_table"] ;
                        $alias_to_be_used=$edge["to_table"];// <<<<<<<<<<<<<<<<<<<<<<<."_".$route_counter;
                    }
                    
                    $route_alias_list_search[$route_counter][] = $table_to_be_joined;
                    $route_alias_list_replace[$route_counter][]=$alias_to_be_used;
                    
                    if ($table_to_be_joined!=$alias_to_be_used) {
                        $query["joins"].="  $join_type join " . $table_to_be_joined . " as ".$alias_to_be_used." \n";
                    } else {
                        $query["joins"].="  $join_type join " . $table_to_be_joined . " \n";
                    }
                    
                    $query["joins"].=" on ";
                    foreach ($edge_list[$edge["to_table"]][$edge["from_table"]]["home_columns"] as $key_c1 => $home_column) {
                        $remote_column=$edge_list[$edge["to_table"]][$edge["from_table"]]["remote_columns"][$key_c1];
                        $query["joins"].= $home_column. " = " . $remote_column . "\n";
                    }
                    if (isset($edge_list[$edge["to_table"]][$edge["from_table"]]["extra_condition"])) {
                        $filter_clause.=" and " . $edge_list[$edge["to_table"]][$edge["from_table"]]["extra_condition"];
                        $query["joins"].= " and " . $edge_list[$edge["to_table"]][$edge["from_table"]]["extra_condition"] . "\n";
                    }
                }
                $route_counter++;
            }
        }

        // add extra condition
        // Merge list of tables needed for queries and joining
        $query["none_reduced_routes"]=$none_reduced_routes;
        $query["reduced_routes"]=$routes;
        $query["sub_selects"]=$sub_selects;

        return $query;
    }

    public function edge_exists_in_routes($edge, $routes)
    {
        foreach ($routes as $key => $route) {
            if ($this->edge_exists_in_route($edge, $route)) {
                return true;
            }
        }
        return false;
    }

    public function edge_exists_in_route($edge, $route)
    {
        foreach ($route as $compare_edge_key => $compare_edge) {
            if ($edge["from_table"] == $compare_edge["from_table"] && $edge["to_table"] == $compare_edge["to_table"]) {
                return true;
            }
        }
        return false;
    }

    public function collect_new_edges($route, $reduced_routes)
    {
        // $reduce_route = array(); // new array for each loop check if edges exist, if not add them.
        foreach ($route as $edge_key => $edge) {
            if (!$this->edge_exists_in_routes($edge, $reduced_routes)) {
                $reduce_route[] = $edge;
            }
        }
        return $reduce_route;
    }

    public function route_reducer($routes)
    {
        $keys=array_keys($routes);
        $first_key=     $keys[0];
        $reduced_routes[0] = $routes[$first_key]; // add the first route to the reduced routes list
        foreach ($routes as $route_key => $route) {
            $reduce_route = $this->collect_new_edges($route, $reduced_routes);
            // print_r($route);
            if (count($reduce_route) > 0) {
                $reduced_routes[] = $reduce_route; // add the reduced route to the list of routes
            }
        }
        return $reduced_routes;
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

?>