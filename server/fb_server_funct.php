<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

/*
file: fb_server_funct.php
This file holds for handling queries and returns content for the client.
see also:

image_functions - <fb_server_image_funct.php>
parameters functions - <fb_server_client_params_funct.php>
*/
require_once __DIR__ . '/lib/dijkstra.php';
require_once __DIR__ . '/connection_helper.php';
require_once __DIR__ . "/../applications/applicationSpecification.php";
require_once __DIR__ . "/../applications/sead/fb_def.php";
require_once __DIR__ . "/lib/SqlFormatter.php";
require_once __DIR__ . '/language/t.php';
require_once __DIR__ . '/fb_server_client_params_funct.php';
require_once __DIR__ . '/html_render.php';
require_once __DIR__ . '/facet_content_loader.php';

/*
* Funnction: *  render_column_meta_data
* Copy definition data
*/
function render_column_meta_data($result_definition, $resultConfig, $facet_params)
{
    $data_item_counter = 0;
    foreach ($resultConfig["items"] as $result_params_key) {
        // First create header for the column.
        foreach ($result_definition[$result_params_key]["result_item"] as $result_column_type => $result_definition_item) {
            foreach ($result_definition_item as $item_type => $result_item) {
                if ($result_column_type!="sort_item") {
                    $column_meta_data[$data_item_counter]["result_column_type"]=$result_column_type;
                    $column_meta_data[$data_item_counter]["link_url"]=$result_item["link_url"];
                    $column_meta_data[$data_item_counter]["link_label"]=$result_item["link_label"];
                    if ($result_column_type == 'count_item') {
                        $extra_text_info = " <BR>" . t("(antal med värde)", $facet_params["client_language"]) . " ";
                        $column_meta_data[$data_item_counter]["result_column_title"]=t($result_item["text"], $facet_params["client_language"]) ;
                        $column_meta_data[$data_item_counter]["result_column_title_extra_info"]=$extra_text_info;
                    } else {
                        $column_meta_data[$data_item_counter]["result_column_title"]=t($result_item["text"], $facet_params["client_language"]) ;
                        $column_meta_data[$data_item_counter]["result_column_title_extra_info"]="";
                    }
                    $data_item_counter++;
                }
            }
        }
    }
    return $column_meta_data;
}


//***************************************************************************************************************************************************
/*
Function: result_render_list_view_html
Function for retrieving data for the result list and transforming that data into an html table.
Parameters:
$facet_params -  An array containing info about the current view-status.
$resultConfig -  An array containing the statistic variables the user wants to show data for.
*/

function render_data_rows_as_html($conn, $rs, $max_result_display_rows, $header_data, $cache_id)
{
    global $applicationName;
    $row_counter=0;
    while (($row = pg_fetch_assoc($rs) ) && ($row_counter < $max_result_display_rows )|| 1==0) {
        $html_table.= "<TR class=" . ($row_counter % 2 == 0 ? "evenrow" : "oddrow") .">";
        $column_counter=0;
        foreach ($row as $row_item) {
            $java_script="";
            $skip_column=false;
            switch ($header_data[$column_counter]["result_column_type"]) {
                case "link_item":
                    $url=$header_data[$column_counter]["link_url"];
                    if (isset($header_data[$column_counter]["link_label"])) {
                        $link_label=$header_data[$column_counter]["link_label"];
                    } else {
                        $link_label=$row_item;
                    }
                    
                    $row_text="<A HREF=\"$url=$row_item&application_name=$applicationName\" title=\"info\" target=\"blank\" >$link_label</A>";
                    break;
                case "link_item_filtered":
                    $url=$header_data[$column_counter]["link_url"];
                    if (isset($header_data[$column_counter]["link_label"])) {
                        $link_label=$header_data[$column_counter]["link_label"];
                    } else {
                        $link_label=$row_item;
                    }
                    
                    $row_text="<A HREF=\"$url=$row_item&cache_id=$cache_id&application_name=$applicationName\" title=\"info\" target=\"blank\" >$link_label</A>";
                    break;
                case "sort_item":
                    $skip_column=true;
                    break;
                default:
                    $row_text= $row_item;
                    break;
            }
            // add formattting for html-link items
            if (!$skip_column) {
                $html_table.= "<td ".$java_script. ">".$row_text."</td>\n";
                $column_counter++; // this counter is used to know the type of result_item
            }
        }
        $html_table.= "</TR>";
        $row_counter++;
    }
    //
    return $html_table;
}

function render_data_rows_as_array($conn, $rs, $max_result_display_rows, $column_meta_data, $cache_id)
{
    $row_counter=0;
    while (($row = pg_fetch_assoc($rs) ) && ($row_counter < $max_result_display_rows )|| 1==0) {
        $column_counter=0;
        $row_key="".$row_counter;
        foreach ($row as $row_item) {
            $skip_column=false;
            $data_array[$row_key]["row_id"]=$row_counter;
            switch ($column_meta_data[$column_counter]["result_column_type"]) {
                case "link_item":
                    $url=$column_meta_data[$column_counter]["link_url"];
                    $data_array[$row_key][$column_counter]["link_url"]="".$url."=".$row_item."";
                    break;
                case "link_item_filtered":
                    $url=$column_meta_data[$column_counter]["link_url"];
                    $row_text="<A HREF=\"$url=$row_item&cache_id=$cache_id\" title=\"info\" target=\"blank\" >$row_item</A>";
                    break;
                case "sort_item":
                    $skip_column=true;
                    break;
                default:
                    $row_text=$row_item;
                    $data_array[$row_key][$column_counter]["row_text"]=$row_text;
                    break;
            }
            // add formattting for html-link items
            if (!$skip_column) {
                $data_array[$row_key][$column_counter]["cell_type"]=$column_meta_data[$column_counter]["result_column_type"];
                $data_array[$row_key][$column_counter]["result_column_title"]=$column_meta_data[$column_counter]["result_column_title"];
                $data_array[$row_key][$column_counter]["result_column_title_extra"]=$column_meta_data[$column_counter]["result_column_title_extra"];
                $column_counter++; // this counter is used to know the type of result_item
            }
        }
        $row_counter++;
    }
    return $data_array;
}

function xml_encode($array, $indent = false, $i = 0)
{
    if (!$i) {
        /*   $data = ''.($indent?"\r\n":'').'<root>'.($indent?"\r\n":'');*/
    } else {
        $data = '';
    }
    foreach ($array as $k => $v) {
        if (is_numeric($k)) {
            $k = 'item';
        }
        $data .= ($indent?str_repeat("\t", $i):'').'<'.$k.'>';
        if (is_array($v)) {
            $data .= ($indent?"\r\n":'').xml_encode($v, $indent, ($i+1)).($indent?str_repeat("\t", $i):'');
        } else {
            $data .= "<![CDATA[".$v."]]>";
        }
        $data .= '</'.$k.'>'.($indent?"\r\n":'');
    }
    return $data;
}

function render_result_array_as_xml($result_array)
{
    return xml_encode($result_array);
}

function result_render_list_view_xml($conn, $facet_params, $resultConfig, $data_link, $cache_id, $data_link_text)
{
    global $facet_definition, $result_definition, $max_result_display_rows;
    $q = get_result_data_query($facet_params, $resultConfig). " ";
    if (empty($q)) {
        return "";
    }
    $rs = ConnectionHelper::query($conn, $q);
    $column_meta_data = render_column_meta_data($result_definition, $resultConfig, $facet_params);
    $result_array = render_data_rows_as_array($conn, $rs, $max_result_display_rows, $column_meta_data, $cache_id);
    $result_data_xml = render_result_array_as_xml($result_array);
    return $result_data_xml;
}

function create_result_table_header($tot_records, $tot_columns, $save_data_link_xls, $save_data_link_text) {
    global $max_result_display_rows;
    $use_xls = ($tot_records * $tot_columns) < 10000;
    $phrase = "Your search resulted in $tot_records records.";
    if ($tot_records > $max_result_display_rows)
        $phrase .= "The first $max_result_display_rows records are displayed below ";
    $phrase .= " <a href=\"$save_data_link_text\" id=\"download_link\" >Save data as text to file.</a>";
    if ($use_xls)
        $phrase .= "  <a href=\"$save_data_link_xls\" id=\"download_link2\">Save data to Excel.</a>";
    return $phrase;
}

function result_render_list_view_html($conn, $facet_params, $resultConfig, $data_link, $cache_id, $data_link_text)
{
    global $facet_definition, $result_definition, $max_result_display_rows;
    $q = get_result_data_query($facet_params, $resultConfig);
    if (empty($q)) {
        return "";
    }
    $rs = ConnectionHelper::query($conn, $q);
    $tot_records = pg_num_rows($rs);
    $tot_columns = count($resultConfig["items"]);
    
    $html_table = create_result_table_header($tot_records, $tot_columns, "server/" . $data_link, "server/" .$data_link_text);

    $q=SqlFormatter::format($q, false);
    $html_table.="   <!-- BEGIN SQL -->\n";
    $html_table.="   <!-- ".SqlFormatter::format($q, false)."-->";
    $html_table.="   <!-- END SQL -->\n";
    // Draw the column headlines
    $column_meta_data=  render_column_meta_data($result_definition, $resultConfig, $facet_params);
    $html_table.=HTMLRender::render_html_header_from_array("result_output_table", $column_meta_data);
    $html_table.= "<tbody>";
    $html_table.=render_data_rows_as_html($conn, $rs, $max_result_display_rows, $column_meta_data, $cache_id);
    $html_table.="   <!-- data array  -->";
    $html_table.="   <!-- array -->";
    // Draw the first rows of data.
    $html_table.= "</tbody></TABLE>";
    return $html_table;
}

// make a string of the selection, This is use to make hashcode of the view_state and is used to handle caching of data
function derive_result_selection_str($result_xml)
{
    global $result_definition;
    $xml_object = new SimpleXMLElement($result_xml);
    $result_items_str="";
    $xml_object = $xml_object->result_input;
    foreach ($xml_object->selected_item as $checked) {
        if (!empty($result_definition[(string)$checked])) {
            $result_items_str .= (string)$checked;
        }
    }
    return (string)$result_items_str;
}
/*
* function: get_facet_xml_from_id
* get the facet_xml stored as file using id of file
*
* returns the xml as string
*
*/
function get_facet_xml_from_id($facet_state_id)
{
    $facet_xml_file_location=__DIR__."/../api/cache/".$facet_state_id."_facet_xml.xml";
    return file_get_contents($facet_xml_file_location);
}

function get_result_xml_from_id($result_state_id)
{
    $result_xml_file_location=__DIR__."/../api/cache/".$result_state_id."_result_xml.xml";
    return file_get_contents($result_xml_file_location);
}

function save_facet_xml($conn, $facet_xml)
{
    global $application_name, $cache_seq;
    $q = isset($cache_seq) ? "select nextval('$cache_seq') as cache_id;" : "select nextval('file_name_data_download_seq') as cache_id;";
    $rs5 = ConnectionHelper::query($conn, $q);
    while ($row = pg_fetch_assoc($rs5)) {
        $facet_state_id = $application_name . $row["cache_id"];
    }
    file_put_contents(__DIR__ . "/../api/cache/" . $facet_state_id . "_facet_xml.xml", $facet_xml);
    return $facet_state_id;
}

function prepare_result_params($facet_params, $resultConfig)
{
    // prepares params for theq query builder.
    // return need params.
    // use aggregation level from resultConfig
    // aggregation code.
    // add N/A for single sum_items or remove them?
    //
    global $facet_definition, $result_definition;

    if (empty($resultConfig["items"])) {
        return NULL;
    }

    $f_code = "result_facet";
    $query_column = $facet_definition[$f_code]["id_column"];
    $group_by_str = "";
    $alias_counter = 1;
    $client_language = $facet_params["client_language"];
    $sep = "";

    // Control which columns and tables should be used in the select clause, depending on what is choosen in the gui.
    foreach ($resultConfig["items"] as $item) {
        // The columns are stringed together., first item is the aggregation_level
          if (empty($item) || $result_definition[$item]["result_item"]) {
              continue;
          }
        foreach ($result_definition[$item]["result_item"] as $res_def_key => $definition_item) {
            foreach ($definition_item as $item_type => $item) {
                $alias_name = "alias_" . $alias_counter++;
                $data_fields_alias .= $sep . $item["column"] . "  AS " . $alias_name;
                $data_tables[] = $item["table"];
                $group_by_str_inner .= $sep . $alias_name;
                switch ($res_def_key) {
                    case "sum_item":
                        $data_fields .= $sep . "sum(" . $alias_name . "::double precision) AS sum_of_" . $alias_name;
                        break;
                    case "count_item":
                        $data_fields .= $sep . "count(" . $alias_name . ") AS count_of_" . $alias_name;
                        break;
                    case "avg_item":
                        $data_fields .= $sep . "avg(" . $alias_name . ") AS avg_of_" . $alias_name;
                        break;
                    case "text_agg_item":
                        $data_fields .= $sep . "array_to_string(array_agg(distinct " . $alias_name . "),',') AS text_agg_of_" . $alias_name;
                        break;
                    case "sort_item":
                        $sort_fields .= $sep . $alias_name;
                        $group_by_str .= $sep . $alias_name;
                        break;
                    case "single_item":
                    default:
                        $data_fields .= $sep . $alias_name;
                        $group_by_str .= $sep . $alias_name;
                        break;
                }
                $sep = " , ";
            }
        }
    }
    if (!empty($data_tables)) {
        $data_tables = array_unique($data_tables); // Removes multiple instances of same table.
    }
    $return_object["data_fields"] = $data_fields;
    $return_object["group_by_str"] = $group_by_str;
    $return_object["group_by_str_inner"] = $group_by_str_inner;
    $return_object["data_fields_alias"] = $data_fields_alias;
    $return_object["sort_fields"] = $sort_fields;
    $return_object["data_tables"] = $data_tables;
    return $return_object;
}

//***************************************************************************************************************************************************
//
/*
function: get_result_data_query
Function the generates the sql-query of html-output and data to download
there are different type of variables which affects the aggregation functinoality in the query.
It uses the "result_facet as a starting point and adds all the selected variables to be included in the output.
For aggregated values there is countíng column being defined for each result variable
see also:
<get_facet_content>
<get_joins>
*/
function get_result_data_query($facet_params, $resultConfig)
{
    $return_object = prepare_result_params($facet_params, $resultConfig);

    if (empty($return_object) || empty($return_object["data_fields"])) {
        return "";
    }

    $data_fields = $return_object["data_fields"];
    $group_by_str = $return_object["group_by_str"];
    $group_by_str_inner = $return_object["group_by_str_inner"];
    $data_fields_alias = $return_object["data_fields_alias"];
    $sort_fields = $return_object["sort_fields"];
    $data_tables = $return_object["data_tables"];
    $f_code = "result_facet";
    $tmp_list = FacetConfig::getKeysOfActiveFacets($facet_params);
    //Add result_facet as final facet
    $tmp_list[] = $f_code;
    
    $query = get_query_clauses($facet_params, $f_code, $data_tables, $tmp_list);
    $extra_join = $query["joins"];
    $tables = $query["tables"];

    $where_clause = ($query["where"] != '') ? " and  " . $query["where"] : "";
    $group_by_clause = (!empty($group_by_str)) ? " group by $group_by_str  " : "";
    $sort_by_clause = (!empty($sort_fields)) ? " order by $sort_fields " : "";

    $q =<<<EOS
        select $data_fields
        from (
            select $data_fields_alias
            from $tables
              $extra_join
            where 1 = 1  
              $where_clause
            group by $group_by_str_inner
        ) as tmp 
        $group_by_clause
        $sort_by_clause
EOS;

    return $q;
}

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

function get_joins($start_table, $destination_table)
{
    global $join_columns;
    global $f_tables;
    global $ourMap;
    global $matrixWidth;
    
    // check if tables exists in the graph.
    if (!(isset($f_tables[$start_table]) && isset($f_tables[$destination_table]))) {
        echo "Tables $start_table or $destination_table does not exists in the graph check configueration";
        exit;
    }
    
    $start_node = $f_tables[$start_table];
    
    $dijkstra = new Dijkstra($ourMap, I, $matrixWidth);
    $destination_node = $f_tables[$destination_table];
    $dijkstra->findShortestPath($start_node);
    $dijstra_result = $dijkstra->getResultsAsArray($destination_node);
    $sql_str = "";
    foreach ($dijstra_result as $value) {
        $count = 0;
        foreach ($value as $element) {
            // below a graph is used to find the columns for joing the tables following the shortest way from pair of tables
            // start after the first item have been reach
            if ($count > 0) {
                $table1 = array_search($prev_value, $f_tables);
                $table2 = array_search($element, $f_tables);
                $remote_columns = $join_columns[$table1][$table2]["remote_columns"];
                $home_columns = $join_columns[$table1][$table2]["home_columns"];
                if (!isset($join_list_spec[$table1][$table2]) && !isset($join_list_spec[$table2][$table1])) {
                    $join_list_spec[$table1][$table2] = $join_columns[$table1][$table2]["join_condition"];
                }
                
                $counter = 0;
                while ($counter < count($remote_columns) && $counter < count($home_columns)) {
                    $sql_str.= $home_columns[$counter] . " = " . $remote_columns[$counter] . " and \n ";
                    $sql_list[$home_columns[$counter]][$remote_columns[$counter]] = true;
                    $connected_tables[$table1][$table2] = true;
                    $counter++;
                }
                
                if (!empty($join_columns[$table1][$table2]["extra_condition"])) {
                    $sql_str.=$join_columns[$table1][$table2]["extra_condition"] . " AND \n ";
                    $extra_conditions.=$join_columns[$table1][$table2]["extra_condition"] . "         AND \n";
                }
            }
            
            $table_joins[array_search($element, $f_tables)] = true; // add tables in the path to be used in queries
            $prev_value = $element;
            $count++;
        }
    }
    $result_obj["extra_conditions"] = $extra_conditions;
    $result_obj["sql_str"] = substr($sql_str, 0, strlen($sql_str) - 7); // remove trailing " AND "
    // Return/build the array that holds the tables needed for the joining.
    foreach ($table_joins as $key => $values) {
        $result_obj["tables_array"][$key] = true;
    }
    
    $result_obj["sql_list"] = $sql_list;
    $result_obj["join_list"] = $join_list_spec;
    $result_obj["connected_tables"] = $connected_tables;
    return $result_obj;
}

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

class query_builder
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
    
    public function get_query_information($edge_list, $numeric_edge_list, $lookup_list_tables, $facet_definition, $facet_params, $f_code, $extra_tables, $f_list)
    {
        global $list_of_alias_tables;
        $query = array();
        $table_list = array();
        $facet_selections = FacetConfig::getItemGroupsSelectedByUser($facet_params);
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

//***************************************************************************************************************************************************

/*
Function: get_query_clauses
This the core of the dynamic query builder.     It's input are previous selected filters and the code of the facet that triggered the action.
It is also use an array of filter to filter by text each facet result,
Parameters:
* $paramss all facet_params, selections, text_filter, positions of facets ie the view.state of the client)
* the target facet to which the query should populate/compute counts etc
* $data_tables, any extra tables that should be part of the query, the function uses the tables via get_joins to join the tables
* $f_list, the list of the facets in the view-state
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
function get_query_clauses($params, $f_code, $extra_tables, $f_list)
{
    global $facet_definition, $join_columns, $f_tables, $ourMap;
    $query_builder= new query_builder();
    $query_info = $query_builder->get_query_information( $join_columns, $ourMap, $f_tables, $facet_definition, $params, $f_code, $extra_tables, $f_list );
    return $query_info;
}

function get_range_counts($conn, $f_code, $params, $q_interval, $direct_count_table, $direct_count_column)
{
    global $facet_definition;
    
    $direct_counts = array();
    $combined_list = array();
    $query_table = $facet_definition[$f_code]["table"];
    $data_tables[] = $query_table;
    $data_tables[] = $direct_count_table;
    
    $f_list = FacetConfig::getKeysOfActiveFacets($params);
    
    //check if f_code exist in list, if not add it. since counting can be done also in result area and then using "abstract facet" that are not normally part of the list
    if (!in_array($f_code, $f_list)) {
        $f_list[] = $f_code;
    }
    
    // use the id's to compute direct resource counts
    // filter is not being used be needed as parameter
    $query = get_query_clauses($params, $f_code, $data_tables, $f_list);
    $query_column = $facet_definition[$f_code]["id_column"] . "::integer";
    $query_tables = $query["tables"];
    $where_clause = $query["where"] != '' ? " and  " . $query["where"] . " " : "";
    $extra_join = $query["joins"] != "" ?  "  " . $query["joins"] . "  " : "";

    $q =<<<EOX
        select lower, upper, facet_term, count(facet_term) as direct_count
        from (select  COALESCE(lower||'=>'||upper, 'data missing') as facet_term,group_column, lower,upper
              from  ( select lower,upper , $direct_count_column  as group_column
                      from $query_tables
                      left  join ( $q_interval ) as temp_interval
                        on $query_column >= lower
                       and $query_column < upper
                      $extra_join $where_clause
                      group by lower, upper ,$direct_count_column
                      order by lower) as tmp4
             group by lower, upper, group_column) as tmp3
        where lower is not null
          and upper is not null
        group by lower, upper, facet_term
        order by lower, upper
EOX;

    if (($rs = pg_exec($conn, $q)) <= 0) {
        echo "Error: cannot execute query2b. direct counts  $q\n";
        pg_close($conn);
        exit;
    }
    while ($row = pg_fetch_assoc($rs)) {
        $facet_term = $row["facet_term"];
        $direct_counts["$facet_term"] = $row["direct_count"];
    }
    
    $combined_list["list"] = $direct_counts;
    $combined_list["sql"]= SqlFormatter::format($q, false);
    return $combined_list;
}

//***************************************************************************************************************************************************
/*
* insert an item an array
*/
function insert_item_before_search_item($f_list, $requested_facet, $insert_item)
{
    if (!isset($f_list)) {
        $new_list[]=$insert_item;
        return $new_list;
    }
    foreach ($f_list as $key => $f_list_element) {
        if ($requested_facet==$f_list_element) {
            //insert count facet before
            $new_list[]=$insert_item;
        }
        $new_list[]=$f_list_element;
    }
    return $new_list;
}
/*
function: get_discrete_count_query
get sql-query for counting of discrete-facet
*/
function get_discrete_count_query2($count_facet, $facet_params, $summarize_type = "count")
{
    global $facet_definition;
    $f_list = FacetConfig::getKeysOfActiveFacets($facet_params);
    if (!empty($facet_params["requested_facet"])) {
        $requested_facet=$facet_params["requested_facet"];
        $extra_tables[]=isset($facet_definition[$requested_facet]["alias_table"]) ? $facet_definition[$requested_facet]["alias_table"]:    $facet_definition[$requested_facet]["table"];
    } else {
        $requested_facet=$count_facet;
    }
    
    $extra_tables[]=$facet_definition[$count_facet]["table"];
    if (!empty($facet_definition[$requested_facet]["query_cond_table"])) {
        foreach ($facet_definition[$requested_facet]["query_cond_table"] as $cond_table) {
            $extra_tables[] =$cond_table;
        }
    }
    
    $f_list = insert_item_before_search_item($f_list, $requested_facet, $count_facet);
    
    $query = get_query_clauses($facet_params, $count_facet, $extra_tables, $f_list);

    $count_column = $facet_definition[$count_facet]["id_column"];
    $requested_facet_column = $facet_definition[$requested_facet]["id_column"];
    $query_tables = $query["tables"];
    $extra_join = ($query["joins"] != "") ? $query["joins"] . "  " : "";
    $extra_where = ($query["where"] != '')  ? " and  " . $query["where"] . " " : "";

    $q = <<<EOT

    select facet_term, $summarize_type(summarize_term) as direct_count
    from (
        select $requested_facet_column  as facet_term , $count_column as summarize_term
        from $query_tables
            $extra_join
        where 1 = 1
            $extra_where
        group by $count_column, $requested_facet_column
    ) as tmp_query
    group by facet_term;

EOT;

    //$q = SqlFormatter::format($q, false);
    return $q;
}


//***********************************************************************************************************************************************************************
/*
Function: get_discrete_counts
Arguments:
* table over which to do counting
* column over which to do counting
* payload (interval for range facets, not used for discrete facets)
Returns:
associative array with counts, the keys are the facet_term i.e the unique id of the row
*/

function get_discrete_counts($conn, $f_code, $facet_params, $payload, $direct_count_table, $direct_count_column)
{
    global $facet_definition;
    $direct_counts = array();
    $combined_list = array();
    $data_tables[] = $direct_count_table;
    $query_table =isset($facet_definition["alias_table"]) ? $facet_definition["alias_table"] : $facet_definition[$f_code]["table"];
    $data_tables[] = $query_table;
    $f_list = FacetConfig::getKeysOfActiveFacets($facet_params);
    //check if f_code exist in list, if not add it. since counting can be done also in result area and then using "abstract facet" that are not normally part of the list
    if (isset($f_list)) {
        if (!in_array($f_code, $f_list)) {
            $f_list[] = $f_code;
        }
    }
    // use the id's to compute direct resource counts
    // filter is not being used be needed as parameter
    $count_facet = $facet_definition[$f_code]["count_facet"] ?? "result_facet";
    $summarize_type = $facet_definition[$f_code]["summarize_type"] ?? "count";
    $q = get_discrete_count_query2($count_facet, $facet_params, $summarize_type);
    $max_count = 0;
    $min_count = 99999999999999999;
    $rs = ConnectionHelper::execute($conn, $q);
    while ($row = pg_fetch_assoc($rs)) {
        $facet_term = $row["facet_term"];
        if ($row["direct_count"] > $max_count) {
            $max_count = $row["direct_count"];
        }
        if ($row["direct_count"] < $min_count) {
            $min_count = $row["direct_count"];
        }
        $direct_counts["$facet_term"] = $row["direct_count"];
    }

    $combined_list["list"] = $direct_counts;
    $combined_list["sql"]=  $q ;
    return $combined_list;
}

class RowFinder {
    /*
    Function: find_start_row
    Parameters:
    * $rows of the particular facet
    * $search_str the text string to position the facet
    Returns: row of facet where the search element is closest to found
    see also
    <find_start_row_linear>
    <find_closest_by_binary_search>
    */

    public static function find_start_row($rows, $search_str)
    {
        $pos = RowFinder::find_start_row_linear($rows, $search_str);
        if ($pos == -1) {
            $pos = RowFinder::find_closest_by_binary_search($rows, $search_str);
        }
        return $pos;
    }

    /*
    Function: find_start_row_linear
    Parameters:
    * $rows of the particular facet
    * $search_str the text string to position the facet
    Returns:
    row of facet where the search element is closest to found
    */

    private static function find_start_row_linear($rows, $search_str)
    {
        // FIXME: Optimize!
        $position = 0;
        $found = "false";
        if (isset($rows)) {
            foreach ($rows as $key => $row) {
                $compare_to_str = substr($row["name"], 0, strlen($search_str));
                $search_str = mb_strtoupper($search_str, "utf-8");
                if (strcasecmp($search_str, $compare_to_str) == 0) {
                    $found = "true";
                    return $position;
                }
                $position++;
            }
        }
        if ($found == "false") {
            $return_position = -1;
            return -1;
        }
    }

    /*
    Function:  find_closest_by_binary_search
    Parameters:
    * $rows of the particular facet
    * $search_str the text string to position the facet
    */

    private static function find_closest_by_binary_search($rows, $search_str, $key = "name")
    {
        $row_counter = 0;
        $start_position = 0;
        $search_str = mb_strtoupper($search_str, "utf-8");
        $end_position = count($rows) - 1;
        $position = floor(($end_position - $start_position) / 2);
        if (!empty($rows)) {
            $found = false;
            while (($end_position - $start_position) > 1 && $found == false) {
                $row = $rows[$position];
                $compare_to_str = substr($row[$key], 0, strlen($search_str));
                if (strcasecmp($search_str, $compare_to_str) == 0) {
                    $found = true;
                } elseif (strcasecmp($search_str, $row[$key]) < 0) {
                    $start_position = $start_position;
                    $end_position = $position;
                    $position = $start_position + floor(($end_position - $start_position) / 2);
                } elseif (strcasecmp($search_str, $row[$key]) > 0) {
                    $start_position = $position;
                    $end_position = $end_position;
                    $position = $start_position + ceil(($end_position - $start_position) / 2);
                }
            }
        }
        return $position;
    }
}

?>
