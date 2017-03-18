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
require_once __DIR__ . "/../applications/sead/custom_server_funct.php";
require_once __DIR__ . "/lib/SqlFormatter.php";
require_once __DIR__ . "/lib/imageSmoothArc_optimized.php";
require_once __DIR__ . '/language/t.php';
require_once __DIR__ . '/lib/fb_server_image_funct.php';
require_once __DIR__ . '/fb_server_client_params_funct.php';

/*
* Funnction: *  render_column_meta_data
* Copy definition data
*/
function render_column_meta_data($result_definition, $result_params, $facet_params)
{
    $data_item_counter = 0;
    foreach ($result_params["items"] as $result_params_key) {
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

function render_html_header_from_array($table_id, $header_array)
{
    $html="<TABLE id=\"".$table_id."\">\n";
    $html.="<thead><TR>\n";
    foreach ($header_array as $data_item_counter => $data_element) {
        $html.="<th>".$data_element["result_column_title"]." ".$data_element["result_column_title_extra_info"]."</th>";
    }
    $html.="</TR></thead>\n";
    return $html;
}

function render_list_data_view($conn, $facet_params, $result_params, $data_link, $cache_id, $data_link_text)
{
    global $facet_definition, $result_definition,  $last_facet_query, $max_result_display_rows;
    $column_meta_data= render_header_data($result_definition, $result_params);
    // get the query to populate the list
    $q= get_result_data_query($facet_params, $result_params). " ";
    json_encode($header_array);
}

//***************************************************************************************************************************************************
/*
Function: result_render_list_view
Function for retrieving data for the result list and transforming that data into an html table.
Parameters:
$facet_params -  An array containing info about the current view-status.
$result_params -  An array containing the statistic variables the user wants to show data for.
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
                // case "image_item":
                //     $file_name=render_image_from_id_detail($conn, $row_item);
                //     $row_text="<IMG title=\"show on map\" style=\"cursor:pointer\"  SRC=\"".$file_name."\" >";
                //     $java_script=render_java_script_from_id($conn, $row_item);
                //     break;
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
            // add formatting for image_items
            switch ($column_meta_data[$column_counter]["result_column_type"]) {
                // case "image_item":
                //     $file_name=render_image_from_id_detail($conn, $row_item, 604, "nobackground");
                //     $java_script=render_java_script_from_id($conn, $row_item);
                //     $data_array[$row_key][$column_counter]["image_src"]= $file_name;
                //     $data_array[$row_key][$column_counter]["image_title"]="show on map";
                //     $data_array[$row_key][$column_counter]["java_script_function"]="zoom_to_bounds";
                //     $column_meta_data[$data_item_counter]["result_column_title"]=t($result_item["text"], $facet_params["client_language"]) ;
                //     $java_script_arguments=render_java_script_arguments($conn, $row_item);
                //     if (is_array($java_script_arguments)) {
                //         foreach ($java_script_arguments as $jkey => $j_argument) {
                //                 $data_array[$row_key][$column_counter]["arguments"][$jkey]=$j_argument;
                //         }
                //     }
                //     break;
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

function result_render_list_view_xml($conn, $facet_params, $result_params, $data_link, $cache_id, $data_link_text)
{
    global $facet_definition, $result_definition,  $last_facet_query, $max_result_display_rows;
    $q = get_result_data_query($facet_params, $result_params). " ";
    $rs = ConnectionHelper::query($conn, $q);
    $column_meta_data=  render_column_meta_data($result_definition, $result_params, $facet_params);
    $result_array=render_data_rows_as_array($conn, $rs, $max_result_display_rows, $column_meta_data, $cache_id);
    $result_data_xml= render_result_array_as_xml($result_array);
    return $result_data_xml;
}

function result_render_list_view($conn, $facet_params, $result_params, $data_link, $cache_id, $data_link_text)
{
    global $facet_definition, $result_definition,  $last_facet_query, $max_result_display_rows;
    $q = get_result_data_query($facet_params, $result_params). " ";
    $rs = ConnectionHelper::query($conn, $q);
    $html_table = create_custom_result_table_header($rs, $facet_params, $result_params, "server/" . $data_link, $cache_id, "server/" .$data_link_text);
    $q=SqlFormatter::format($q, false);
    $html_table.="   <!-- BEGIN SQL -->\n";
    $html_table.="   <!-- ".SqlFormatter::format($q, false)."-->";
    $html_table.="   <!-- END SQL -->\n";
    // Draw the column headlines
    $column_meta_data=  render_column_meta_data($result_definition, $result_params, $facet_params);
    $html_table.=render_html_header_from_array("result_output_table", $column_meta_data);
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
        if (!empty($result_definition[(string) $checked])) {
            $result_items_str.=  (string) $checked;
        }
    }
    return  (string) $result_items_str;
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
    $facet_xml_file_location=__DIR__."/cache/".$facet_state_id."_facet_xml.xml";
    return file_get_contents($facet_xml_file_location);
}

function get_result_xml_from_id($result_state_id)
{
    $result_xml_file_location=__DIR__."/cache/".$result_state_id."_result_xml.xml";
    return   file_get_contents($result_xml_file_location);
}

function save_facet_xml($conn, $facet_xml)
{
    global $application_name, $cache_seq;
    $q = isset($cache_seq) ? "select nextval('$cache_seq') as cache_id;" : "select nextval('file_name_data_download_seq') as cache_id;";
    $rs5 = ConnectionHelper::query($conn, $q);
    while ($row = pg_fetch_assoc($rs5)) {
        $facet_state_id = $application_name . $row["cache_id"];
    }
    file_put_contents(__DIR__ . "/cache/" . $facet_state_id . "_facet_xml.xml", $facet_xml);
    return $facet_state_id;
}

/*
function: inverse_array
function to inverse an array
Returns reversed array
*/

function inverse_array($array_in)
{
    while (list($key, $val) = each($array_in)) {
        $inverse_array_in[$val] = $key;
    }
    return $inverse_array_in;
}

//***************************************************************************************************************************************************
/*
function:  format_string

Description:
Inserts text strings supplied in an associate array into the supplied text string.
This function works by replacing standard tokens with the string specified in the
second argument. It also only replaces one item at a time so that consequtive
tokens of the same type are replaces by different strings, if more than one token
is given as key in the supplied array.

In order to be able to supply several keys of the same token, the value of the token key
can be an array.
The tokens used are specified in the replacement tokens section of the applicationSpecification.php file.
parmeters:
* @param string $tokenized_string The string that contain the markup tokens.
* @param array $replacement_array The array defining the replacement values, keyed by the tokens
and allow both single values and array values for the keys.
*

returns:
string the tokenized_string with the tokens replaced by the values in the supplied array.

Author:
Erik E.

*/
function format_string($tokenized_string, $replacement_array)
{
    $string_copy = $tokenized_string; // copy the string for good measure.
    foreach ($replacement_array as $token => $values) {
        if (is_array($values)) {
            foreach ($values as $value) {
                $first_occurance = strpos($string_copy, $token);
                if ($first_occurance === false) {
                    break;
                }
                $string_copy = substr_replace($string_copy, $values, $first_occurance, strlen($token));
            }
        } else {
            //if the supplied string contain more than one token of the same kind
            //yet only one value for that token, replace all occurances of that
            //token with the given value.
            $parts = explode($token, $str_copy);
            if (count($parts) > 1) {
                $string_copy = str_replace($token, $value, $string_copy);
            } else {
                $first_occurance = strpos($string_copy, $token);
                if ($first_occurance !== false) {
                    $string_copy = substr_replace($string_copy, $values, $first_occurance, strlen($token));
                }
            }
        }
    }
    return $string_copy;
}

function prepare_result_params($facet_params, $result_params)
{
    // prepares params for theq query builder.
    // return need params.
    // use aggregation level from result_params
    // aggregation code.
    //  use asum level unless
    // if any item is on Linnosa /county level
    // if any item is on city level, use city level aggregation
    // add N/A for single sum_items or remove them?
    //
    global $facet_definition, $result_definition;
    $f_code = "result_facet";
    $query_column = $facet_definition[$f_code]["id_column"];
    $group_by_str = "";
    $alias_counter = 1;
    $use_count_item = false;
    $client_language = $facet_params["client_language"];
    // Control which columns and tables should be used in the select clause, depending on what is choosen in the gui.
    foreach ($result_params["items"] as $item) {
        // The columns are stringed together., first item is the aggregation_level
        // if the aggregation level is not Parish then use the count_item for each result_variable
        if ($alias_counter == 1 && $item != "parish_level") {
            $use_count_item = true;
        }
        foreach ($result_definition[$item]["result_item"] as $res_def_key => $definition_item) {
            foreach ($definition_item as $item_type => $item) {
                $alias_name = "alias_" . $alias_counter++;
                if ($item["use_translation"]==1 && isset($item["use_translation"])) {
                    $item["column"]="t(". $item["column"] . "::text,'". $client_language . "'  ) ";
                }
                $data_fields_alias.=" " . $item["column"] . "  AS " . $alias_name . ",";
                switch ($res_def_key) {
                    case "sum_item":
                        $data_fields.="sum(" . $alias_name . "::double precision) AS sum_of_" . $alias_name . ",";
                        //the tables are stored in an array.
                        $data_tables[] = $item["table"];
                        $group_by_str_inner.=$alias_name. ",";
                        break;
                    case "count_item":
                        if ($use_count_item) {
                            $data_fields.="count(" . $alias_name . ") AS count_of_" . $alias_name . ",";
                            //the tables are stored in an array.
                            $data_tables[] = $item["table"];
                            $group_by_str_inner.=$alias_name. ",";
                    }
                    break;
                    case "avg_item":
                        $data_fields.="avg(" . $alias_name . ") AS avg_of_" . $alias_name . ",";
                        //the tables are stored in an array.
                        $data_tables[] = $item["table"];
                        $group_by_str_inner.=$alias_name. ",";
                        break;
                    case "text_agg_item":
                        $data_fields.="array_to_string(array_agg(distinct " . $alias_name . "),',') AS text_agg_of_" . $alias_name . ",";
                        //the tables are stored in an array.
                        $data_tables[] = $item["table"];
                        $group_by_str_inner.=$alias_name. ",";
                        break;
                    case "sort_item":
                        $sort_fields.=$alias_name . ",";
                        //the tables are stored in an array.
                        $data_tables[] = $item["table"];
                        $group_by_str.=$alias_name . ",";
                        $group_by_str_inner.=$alias_name. ",";
                        break;
                    case "single_item":
                    default:
                        $data_fields.=$alias_name . ",";
                        //the tables are stored in an array.
                        $data_tables[] = $item["table"];
                        $group_by_str.=$alias_name . ",";
                        $group_by_str_inner.=$alias_name. ",";
                        break;
                }
            }
        }
    }
    // Remove last coma.
    $data_fields = substr($data_fields, 0, strlen($data_fields) - 1);
    $group_by_str = substr($group_by_str, 0, strlen($group_by_str) - 1);
    $group_by_str_inner.=substr($group_by_str_inner, 0, strlen($group_by_str_inner) - 1);
    $data_fields_alias = substr($data_fields_alias, 0, strlen($data_fields_alias) - 1);
    $sort_fields = substr($sort_fields, 0, strlen($sort_fields) - 1);

    // Remove multiple instances of tables.
    $data_tables = array_unique($data_tables);
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
function get_result_data_query($facet_params, $result_params)
{
    $return_object = prepare_result_params($facet_params, $result_params);
    $data_fields = $return_object["data_fields"];
    $group_by_str = $return_object["group_by_str"];
    $group_by_str_inner = $return_object["group_by_str_inner"];
    $data_fields_alias = $return_object["data_fields_alias"];
    $sort_fields = $return_object["sort_fields"];
    $data_tables = $return_object["data_tables"];
    $f_code = "result_facet";
    $tmp_list = derive_facet_list($facet_params);
    //Add result_facet as final facet
    $tmp_list[] = $f_code;
    
    $query = get_query_clauses($facet_params, $f_code, $data_tables, $tmp_list);
    $extra_join = $query["joins"];
    $table_str = $query["tables"];
    $q = " select $data_fields from ( ";
    $q.="select   " . $data_fields_alias . " from " . $table_str . "   $extra_join   where 1=1  ";
    
    if ($query["where"] != '') {
        $q.=" and  " . $query["where"];
    }
    
    $q.="  group by  $group_by_str_inner ) as tmp ";
    if (!empty($group_by_str)) {
        $q.=" group by $group_by_str  ";
    }
    if (!empty($sort_fields)) {
        $q.=" order by $sort_fields";
    }
    return $q;
}

function generate_temporary_name()
{
    srand((double) microtime() * 1000000);
    $temp_table = "database_graph_" . rand(0, 10000000);
    return $temp_table;
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

/*
function: get_geo_box_from_selection
To be used to calculate how many observation for each map-rectangle selection
*/

function get_geo_box_from_selection($current_selection_group)
{
    $current_selection_group = (array) $current_selection_group;
    $count_boxes = 0;
    foreach ($current_selection_group as $key2 => $current_selection) {
        if (isset($current_selection)) {
            // loop through the different types of values  in the group
            foreach ($current_selection as $key5 => $selection_t) {
                $selection_t = (array) $selection_t;
                $this_selection_a[$selection_t["selection_type"]] = $selection_t["selection_value"];
            }
            $e1 = $this_selection_a["e1"];
            $n1 = $this_selection_a["n1"];
            $e2 = $this_selection_a["e2"];
            $n2 = $this_selection_a["n2"];
            $box_list[] = " SetSRID('BOX3D($e1 $n1,$e2 $n2)'::box3d,4326) ";
            $id_list[] = $this_selection_a["rectangle_id"];
            $count_boxes++;
        }
    }
    $return_obj["box_list"] = $box_list;
    $return_obj["id_list"] = $id_list;
    $return_obj["count_boxes"] = $count_boxes;
    
    return $return_obj;
}

/*
function: get_geo_filter_clauses
loop through selections and builds the sql for geo_filter, one or many areas (rectangles representative by 2 pair of coordinates
*/

function get_geo_filter_clauses($f_code, $skey, $current_selection_group)
{
    global $facet_definition;
    $box_list = get_geo_box_from_selection($current_selection_group);
    $box_list = $box_list["box_list"];
    $query_temp = "";
    $geo_column = $facet_definition[$skey]["geo_filter_column"];
    foreach ($box_list as $box_key => $box) {
        $query_temp.="\n $geo_column && " . $box . " and ST_Intersects( $geo_column," . $box . ") \n  or ";
    }
    $query_temp = substr($query_temp, 0, -3);
    $or_include="";
    $query_where.="(( " . $query_temp . " ) $or_include)  ";
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

//***************************************************************************************************************************************************
/*
function:  get_discrete_selection_values
get the selection value from a selection group from the facet_xml-data array
*/

function get_discrete_selection_values($current_selection_group)
{
    if (isset($current_selection_group)) {
        foreach ($current_selection_group as $key2 => $sval) {
            foreach ($sval as $key5 => $selection) {
                foreach ($selection as $temp_key => $selection_element) {
                    $selection_element = (array) $selection_element;
                    $selection_values[] = (string) $selection_element["selection_value"];
                }
            }
        }
    }
    return $selection_values;
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
        $facet_selections = derive_selections($facet_params);
        if (isset($facet_definition[$f_code]["query_cond_table"]) && !empty($facet_definition[$f_code]["query_cond_table"])) {
            foreach ($facet_definition[$f_code]["query_cond_table"] as $cond_key => $cond_table) {
                $extra_tables[] = $cond_table;
            }
        }
        
        if (isset($f_list)) {
            $f_list_positions = array_flip($f_list);
            // the list needs to be set to be flipped key to values
        }
        
        $query_column = $query_where = "";
        
        if (isset($f_list)) {
            // list must exist, ie there must be some filters in order build a query
            foreach ($f_list as $pos => $facet) {
                if (isset($facet_selections[$facet])) {
                    while (list($skey1, $selection_group) = each($facet_selections[$facet])) {
                        // tricky condition here!2009-11-28
                        if ($f_list_positions[$f_code] > $f_list_positions[$facet] || ($facet_definition[$f_code]["facet_type"] == "range" &&
                            $f_list_positions[$f_code] == $f_list_positions[$facet]) || ($facet_definition[$f_code]["facet_type"] == "geo" &&
                        $f_list_positions[$f_code] == $f_list_positions[$facet])) {               // only being affected to the down but always for itself for ranges since we need to update the histogram and map filter with new intervals
                            
                            $table_with_selections=  isset($facet_definition[$facet]["alias_table"]) ? $facet_definition[$facet]["alias_table"] : $facet_definition[$facet]["table"] ;
                            
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
                        }  // end of check if selection should affect facet.
                    }
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

//get_range_counts($q1,$params,$f_code)
function get_range_counts($conn, $params, $q_interval, $f_code, $query, $direct_count_column, $direct_count_table)
{
    global $facet_definition;
    
    $direct_counts = array();
    $combined_list = array();
    $row_counter = 1;
    $query_table = $facet_definition[$f_code]["table"];
    $data_tables[] = $query_table;
    $data_tables[] = $direct_count_table;
    
    $f_list = derive_facet_list($params);
    
    //check if f_code exist in list, if not add it. since counting can be done also in result area and then using "abstract facet" that are not normally part of the list
    if (!in_array($f_code, $f_list)) {
        $f_list[] = $f_code;
    }
    
    // use the id's to compute direct resource counts
    // filter is not being used be needed as parameter
    $query = get_query_clauses($params, $f_code, $data_tables, $f_list);
    $query_column = $facet_definition[$f_code]["id_column"] . "::integer";

    if ($query["joins"] != "") {
        $extra_join =  "  ".$query["joins"] . "  ";
    }
    $q = "select lower,upper,facet_term,count(facet_term)  as direct_count from (select  COALESCE(lower||'=>'||upper, 'data missing') as facet_term,group_column, lower,upper";
    $q.= " from       ( select lower,upper , $direct_count_column  as group_column from " . $query["tables"] . "          left  join ";
    $q.=" ( $q_interval ) as temp_interval  on   $query_column>=lower and $query_column<upper  $extra_join    ";
    
    if ($query["where"] != '') {
        $q.=" and  " . $query["where"] . " ";
    }
    
    $q.=" group by lower,upper ,$direct_count_column order by lower )  as tmp4  group by lower,upper, group_column )  as tmp3 "
    . " where lower is not null and upper is not null "
    . "group by lower,upper,facet_term  order by lower,upper";
    
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
    $f_list = derive_facet_list($facet_params);
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
    
    $f_list=insert_item_before_search_item($f_list, $requested_facet, $count_facet);
    
    $query = get_query_clauses($facet_params, $count_facet, $extra_tables, $f_list); // mapped to new function
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

/*
function: get_discrete_count_query
get sql-query for counting of discrete-facet
*/
function get_discrete_count_query($f_code, $query, $direct_count_column)
{
    global $facet_definition;
    
    $query_tables = $query["tables"];
    $query_column = $facet_definition[$f_code]["id_column"];
    $extra_join = ($query["joins"] != "") ? $query["joins"] . "  " : "";
    $extra_where = ($query["where"] != '') ? " and  " . $query["where"] . " " : "";

    $q  = <<<EOD
        select facet_term, count(facet_term) as direct_count
        from (
            select $query_column as facet_term
            from $query_tables
                 $extra_join
            where 1 = 1 
              $extra_where
            group by $query_column, $direct_count_column
        ) as tmp_query group by facet_term;
EOD;

    return $q;
}

/*
function: get_geo_count_query
get sql-query for counting of geo-facet
l
*/

function get_geo_count_query($facet_params, $f_code, $direct_count_table, $direct_count_column)
{
    global $facet_definition;
    $f_selected = derive_selections($facet_params);
    $geo_selection = $f_selected[$f_code]["selection_group"];
    
    $data_tables[] = $direct_count_table;
    $f_list = derive_facet_list($facet_params);
    $mod_params = $facet_params;
    
    $mod_params["facet_collection"][$f_code]["selection_groups"] = ""; //empty the selections for geo // removed the geo_selection
    $query_table = $facet_definition[$f_code]["table"];
    $data_tables[] = $query_table;
    
    $temp_table = "temp_poly_" . md5($direct_count_column . derive_selections_string($facet_params));
    //maka tempory table with geo-filter only to make a temp output for the polygons
    $temp_geo_sql = "select * into temp " . $temp_table . " from  " . $facet_definition[$f_code]["table"];
    //get the selection as sql for the geo_selection
    $box_list = get_geo_box_from_selection($geo_selection);
    $box_list = $box_list["box_list"];
    
    $query_temp = "";
    if (isset($box_list)) {
        $query_temp = " where  (";
        foreach ($box_list as $box_key => $box) {
            $query_temp.="\n " . $facet_definition[$f_code]["geo_filter_column"] . " && " . $box . " and ST_Intersects( " . $facet_definition[$f_code]["geo_filter_column"] . "," . $box . ") \n  or ";
        }
        $query_temp = substr($query_temp, 0, -4); // remove last or
        $query_temp.=" ) ";
    }
    
    $temp_geo_sql.=$query_temp . "\n;";
    
    $query = get_query_clauses($facet_params, $f_code, $data_tables, $f_list);
    $query_modified = get_query_clauses($mod_params, $f_code, $data_tables, $f_list);
    //replace ships_polygons with temp out but with the alias "ships_polygons"
    $query["tables"] = str_replace($facet_definition[$f_code]["table"], $temp_table . " as " . $facet_definition[$f_code]["table"], $query["tables"]);
    // optimiziation replace ships_polygons with a subset of ships_polygon based on the area name that temp table as ships_polygon
    
    $new_sql = $temp_geo_sql; // add temp table query to the main query
    
    $sql_geo_boxes = "";
    $geo_boxes = get_geo_box_from_selection($geo_selection);
    
    $geo_box_id_list = $geo_boxes["id_list"];
    $geo_boxes = $geo_boxes["box_list"];
    foreach ($geo_boxes as $tkey => $the_geo_box) {
        $rect_id = $geo_box_id_list[$tkey];
        $sql_geo_boxes.=" select  '$rect_id'::text as rectangle_id, " . $the_geo_box . "    union    ";
    }
    
    $sql_geo_boxes = substr($sql_geo_boxes, 0, -10);
    
    // make temp table for performance:
    $temp_table = "temp_" . md5(derive_selections_string($facet_params) . $direct_count_column);
    $new_sql.= "select * into temp $temp_table from  (" . $sql_geo_boxes . ") as geotemp2;";
    $new_sql.="select  rectangle_id as facet_term, area(setsrid),count($direct_count_column) as direct_count from  ";
    if ($query["joins"] != "") {
        $extra_join = $query["joins"] . "  ";
    }
    
    $new_sql.=" $temp_table, " . $query["tables"] . "  $extra_join  where " . $facet_definition[$f_code]["geo_filter_column"] . " && setsrid   ";
    if ($query_modified["where"] != '') {
        $new_sql.=" and  " . $query_modified["where"] . " ";
    }
    $new_sql.=" group by rectangle_id,setsrid";
    
    return SqlFormatter::format($new_sql, false);
}

//***********************************************************************************************************************************************************************
/*
Function: get_counts
Arguments:
* table over which to do counting
* column over which to do counting
* interval for range facets
Returns:
associative array with counts, the keys are the facet_term i.e the unique id of the row
*/

function get_counts($conn, $f_code, $facet_params, $interval = 1, $direct_count_table, $direct_count_column)
{
    global $last_facet_query, $facet_definition;
    $direct_counts = array();
    $combined_list = array();
    $row_counter = 1;
    $data_tables[] = $direct_count_table;
    $query_table =isset($facet_definition["alias_table"]) ? $facet_definition["alias_table"] :$facet_definition[$f_code]["table"];
    $data_tables[] = $query_table;
    $f_list = derive_facet_list($facet_params);
    //check if f_code exist in list, if not add it. since counting can be done also in result area and then using "abstract facet" that are not normally part of the list
    if (isset($f_list)) {
        if (!in_array($f_code, $f_list)) {
            $f_list[] = $f_code;
        }
    }
    // use the id's to compute direct resource counts
    // filter is not being used be needed as parameter
    switch ($facet_definition[$f_code]["facet_type"]) {
        case "discrete":
            $count_facet="result_facet";
            $summarize_type="count";
            if (isset($facet_definition[$f_code]["count_facet"])) {
                $count_facet=$facet_definition[$f_code]["count_facet"];
            }
            if (isset($facet_definition[$f_code]["summarize_type"])) {
                $summarize_type=$facet_definition[$f_code]["summarize_type"];
            }
            $q=get_discrete_count_query2($count_facet, $facet_params, $summarize_type);
            break;
        case "geo":
            $f_selected = derive_selections($facet_params);
            $geo_selection = $f_selected[$f_code]["selection_group"];
            $count_facet="result_facet";
            $box_check = get_geo_box_from_selection($geo_selection); // use this function to check if there are any selections
            if ($box_check["count_boxes"] > 0 && isset($geo_selection)) {
                $q = get_geo_count_query($facet_params, $f_code, $direct_count_table, $direct_count_column);
            } else {
                $q = "select 'null' as facet_term, 0 as direct_count ;";
            }
            break;
    }
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
    $last_facet_query.="-- direct counts query \n  $q; \n";

    $combined_list["list"] = $direct_counts;
    $combined_list["sql"]=  $q ;
    return $combined_list;
}

/*
function: get_common_query
common part of where clauses and joins conditions from the "query_object"
*/

function get_common_query($query)
{
    if (trim($query["where"]) != '') {
        $common_sql = " and \n " . $query["where"];
    }
    return $common_sql;
}

/*
function: get_range_query
get the sql-query for facet with interval data by computing a sql-query by adding the interval number into a sql-text
*/

function get_range_query($interval, $min_value, $max_value, $interval_count)
{
    $pieces = array();
    $interval_counter = 0;
    $lower = $min_value;
    while ($interval_counter <= $interval_count && $lower <= $max_value) {
        $lower = $min_value + $interval * $interval_counter;
        $upper = $min_value + $interval * $interval_counter + $interval;
        $pieces[] = "select " . $lower . " as lower, " . $upper . " as upper, '" . $lower . "=>" . $upper . "'::text as id,'' as name";
        $interval_counter++;
    }
    $q1 = implode("\nunion all ",$pieces);
    return $q1;
}

function get_text_filter_condition($facet_params, $query_column_name)
{
    global $filter_by_text;
    $find_str = trim($facet_params["facet_collection"][$facet_params["requested_facet"]]["facet_text_search"]);
    if ($find_str == "undefined") {
        $find_str = "%";
    }
    return (!empty($find_str) && $filter_by_text == true) ? $query_column_name . " ILIKE '" . $find_str . "' AND \n" : "";
}
/*
function: get_discrete_query
get the sql-query for facet with discrete data
*/

function get_discrete_query($facet_params)
{
    global $direct_count_table, $direct_count_column;
    global $facet_definition;
    $f_code = $facet_params["requested_facet"];
    $f_list = derive_facet_list($facet_params);
    $query = get_query_clauses($facet_params, $f_code, $data_tables, $f_list);
    $query_column_name = $facet_definition[$f_code]["name_column"];
    $query_column = $facet_definition[$f_code]["id_column"];
    $sort_column = $facet_definition[$f_code]["sort_column"];
    $sort_order = $facet_definition[$f_code]["sort_order"];
    $find_cond=  get_text_filter_condition($facet_params, $query_column_name);
    $client_language = $facet_params["client_language"];
    
    $group_by_column_name = $query_column_name; // use this without translation for performance
    if ($facet_definition[$f_code]["use_translation"] == 1) {
        $query_column_name = "t(" . $query_column_name . ",'" . $client_language . "'  )";
        $sort_column = "t(" . $sort_column . ",'" . $client_language . "'  )";
    }
    $tables = $query["tables"];
    $q1 = "select  $query_column  as id , $query_column_name as name \n from " . $tables . " ".$query["joins"]. "  where  $find_cond   1=1 ";
    // ----- BEGINNING of common part of all queries -----//
    $common_part = get_common_query($query);
    $q1.=$common_part;
    // ----- END of common part of all queries -----//
    if (!empty($sort_column)) {
        $q1.= " group by  $sort_column, $query_column , $query_column_name  order by  $sort_column $sort_order";
    } else {
        $q1.= " group by  $query_column , $query_column_name , $query_column ";
    }
    return $q1;
}

/*
function: get_geo_query
make a query for the boxes in the map-filter,the rectangles

*/

function get_geo_query($params)
{
    // FIXME  query
    // make a query for the boxes in the map-filter,the rectange
    global $facet_definition;
    $f_code = $params["requested_facet"];
    $f_list = derive_facet_list($params);
    $f_selected = derive_selections($params);
    $geo_selection = $f_selected[$f_code]["selection_group"];
    $query = get_query_clauses($params, $f_code, $data_tables, $f_list);
    $sql_geo_boxes = "";
    $geo_boxes = get_geo_box_from_selection($geo_selection);
    $id_box_list = $geo_boxes["id_list"];
    $geo_boxes = $geo_boxes["box_list"];
    foreach ($geo_boxes as $tkey => $the_geo_box) {
        $rect_id = $id_box_list[$tkey];
        $sql_geo_boxes.=" select '$rect_id'::text as rectangle_id, " . $the_geo_box . "    union    ";
    }
    $sql_geo_boxes = substr($sql_geo_boxes, 0, -10);
    $new_sql = "select  rectangle_id as id, asgml(setsrid) as name from  ";
    if ($query["joins"] != "") {
        $extra_join = $query["joins"] . "";
    }
    $new_sql.=" ($sql_geo_boxes) as geotemp2  ";
    if (!empty($query["tables"])) {
        $new_sql.=", " . $query["tables"];
    }
    $new_sql.=" $extra_join where  1=1  ";
    if ($query["where"] != '') {
        $new_sql.=" and  " . $query["where"] . " ";
    }
    $new_sql.=" group by rectangle_id,setsrid";
    return SqlFormatter::format($new_sql, false);
}

/*
function: get_extra_row_info
get additional information for a facet_row to be associated with a id in a array
So one in a abstract term merges the content of two facets that has the same table and the same id for a row in the table.
*/

function get_extra_row_info($f_code, $conn, $params)
{
    global $facet_definition, $last_facet_query;
    $query_column = $facet_definition[$f_code]["id_column"];
    $f_list = derive_facet_list($params);
    $query = get_query_clauses($params, $f_code, $data_tables, $f_list);
    $query_column_name = $facet_definition[$f_code]["name_column"];
    $sort_column = $facet_definition[$f_code]["sort_column"];
    $tables = $query["tables"];
    
    $and_clause = "";
    $q1 = "select distinct id, name from (";
    $q1.="SELECT  $query_column  as id , COALESCE($query_column_name,'No value') as name, $sort_column as sort_column \n FROM   " . $tables . "  WHERE 1=1 ";
    if (trim($query["where"]) != '') {
        $q1.=" AND \n " . $query["where"];
    }
    if (trim($query["joins"]) != '' && trim($query["where"]) != '') {
        $and_clause = " AND ";
    }
    if (trim($query["joins"]) != '' && trim($query["where"]) == '') {
        $and_clause = " AND \n ";
    }
    $q1.=" $and_clause \n " . $query["joins"];
    $q1.= $map_filter_query_where;
    
    $q1.= " GROUP BY COALESCE($query_column_name,'No value') ,id , sort_column   order by sort_column";
    $q1.= " ) as tmp ";
    
    $last_facet_query.="Facet load of $f_code <BR>\n" . $q1 . "<BR>\n<BR><HR>\n";
    $rs2 = ConnectionHelper::execute($conn, $q1);
    $row_counter = 1;
    while ($row = pg_fetch_assoc($rs2)) {
        $au_id = $row["id"];
        $extra_row_info[$au_id] = $row["name"];
    }
    return $extra_row_info;
}

// compute max and min for range facet
/*
Function: compute_range_lower_upper
Get the min and max values of filter-variable from the database table.
*/
function compute_range_lower_upper($conn, $facet)
{
    global $facet_definition;
    $query_column = $facet_definition[$facet]["id_column"];
    $query_table = $facet_definition[$facet]["table"];
    $q = "";
    $q.=" select max(" . $query_column . ") as max,  min(" . $query_column . ") as min from " . $query_table;
    if ($q != "") {
        $rs2 = ConnectionHelper::execute($conn, $q);
        while ($row = pg_fetch_assoc($rs2)) {
            $facet_range["upper"] = $row["max"];
            $facet_range["lower"] = $row["min"];
        }
    }
    return $facet_range;
}

/*
function: get_lower_upper_limit
Gets the lower and limits in range-filter so the correct intervals can be computed.
Uses the clients setting if existing, otherwise get it from the database.
*/

function get_lower_upper_limit($conn, $params, $facet)
{
    $f_selected = derive_selections($params);
    if (isset($f_selected[$facet])) {
        foreach ($f_selected[$facet] as $skey => $selection_group) { // dig into the gruops of selection of the facets
            foreach ($selection_group as $skey2 => $selection) { // dig into the group
                foreach ($selection as $skey3 => $selection_bit) { // dig into the particular selection ie type and value
                    $selection_bit = (array) $selection_bit;
                    $limits[$selection_bit["selection_type"]] = $selection_bit["selection_value"];
                }
            }
        }
    } else {
        // If the limits are not set in the facet_xml from the client then use the min and max values from the database
        $limits = compute_range_lower_upper($conn, $facet);
    }
    
    return $limits;
}

/*
Function:  get_facet_content
function to obtain content of facets to the right of the current one
Parameters:
conn , database connection
params - all view-state info.

Returns:
* facet query report, sql only
* Selection in the particular  facets
* start row of the rows requested
* number of rows requested
* total number of rows available
row structure:
*   row id
*   display name
*   (direct) count 1 number of geo-units or what is being defined in fb_def.php
*   (indirect count 2 number of time-periods or  what is being defined in fb_def.php
Preperation function:
<fb_process_params>

Functions used by interval facets:
<get_range_query>

Functions used by discrete and geo facets (geo):
<get_discrete_query>

function used all types of facet:
<get_counts>

Post functions:
<build_xml_response>
*/

function get_facet_content($conn, $params)
{
    global $last_facet_query, $direct_count_table, $direct_count_column, $indirect_count_table, $indirect_count_column,
    $facet_definition;
    $facet_content = $search_string = $query_column_name = "";
    $f_code = $params["requested_facet"];
    $query_column = $facet_definition[$f_code]["id_column"];
    $sort_column = $query_column;
    // compute the intervall for a number of histogram items if it is defined as range facet
    switch ($facet_definition[$f_code]["facet_type"]) {
        case "range":
            // get the limits from the client if existing otherwize get the min and max from the database
            // this is need to match the client handling of the intervals in facet.range.js
            // use the limits to define the size of the interval
            $limits = get_lower_upper_limit($conn, $params, $f_code);
            $min_value = $limits["lower"];
            $max_value = $limits["upper"];
            $interval_count = 120; // should it be lower maybe??? when there is no data
            $interval = floor(($limits["upper"] - $limits["lower"]) / $interval_count);
            if ($interval <= 0) {
                $interval = 1;
        }
        // derive the interval as sql-query although it is not using a table...only a long sql with select the values of the interval
        $q1 = get_range_query($interval, $min_value, $max_value, $interval_count);
        $interval_query = $q1; // use the query later to check how many obesveration for each item
        break;
        case "discrete":
            $q1 = get_discrete_query($params). "  ";
            break;
        case "geo":
            // compute boxes and how many observations in each rectangle in the map.
            $f_code = $params["requested_facet"];
            $f_selected = derive_selections($params);
            $geo_selection = $f_selected[$f_code]["selection_group"];
            $box_check = get_geo_box_from_selection($geo_selection); // use this function to check if there are any selections
            //print_r($box_check);
            if (($box_check["count_boxes"]) > 0) {
                $q1 = get_geo_query($params);
        } else {
            $q1 = "select 'null' as id, 'null' as name ;";
        }
        break;
    }

    $last_facet_query.="--- Facet load of $f_code \n" . $q1 . ";\n";
    $rs2 = Connectionhelper::query($conn, $q1);
    $facet_contents[$f_code]['f_code'] = $f_code;
    $facet_contents[$f_code]['range_interval'] = $interval;
    $facet_contents[$f_code]['f_action'] = $params['f_action'][1];
    $facet_contents[$f_code]['start_row'] = $params[$f_code]['facet_start_row'];
    $facet_contents[$f_code]['rows_num'] = $params[$f_code]['facet_number_of_rows'];
    $facet_contents[$f_code]['total_number_of_rows'] = pg_numrows($rs2);

    pg_result_seek($rs2, 0);
    if (isset($direct_count_column) && !empty($direct_count_column)) {
        if ($facet_definition[$f_code]["facet_type"] == "range") {
            // use a special function of the counting in ranges since it using other parameters
            $direct_counts = get_range_counts($conn, $params, $interval_query, $f_code, $query, $direct_count_column, $direct_count_table);
        } else {
            $direct_counts = get_counts($conn, $f_code, $params, $interval, $direct_count_table, $direct_count_column);
        }
    }
    // add extra information to a facet
    if (isset($facet_definition[$f_code]["extra_row_info_facet"])) {
        $extra_row_info = get_extra_row_info($facet_definition[$f_code]["extra_row_info_facet"], $conn, $params);
    }
    $count_of_selections = derive_count_of_selection($params, $f_code);
    if ($count_of_selections != 0) {
        $tooltip_text = derive_selections_to_html($params, $f_code);
    } else {
        $tooltip_text = "";
    }
    $tooltip_xml = "";

    $facet_contents[$f_code]['report'] = " Aktuella filter   <BR>  ". $tooltip_text."  ".SqlFormatter::format($q1, false)."  ; \n  ".$direct_counts["sql"]." ;\n";
    $facet_contents[$f_code]['report_html'] = $tooltip_text;
    $facet_contents[$f_code]['count_of_selections'] = $count_of_selections;
    $facet_contents[$f_code]['report_xml'] = $tooltip_xml;
    pg_result_seek($rs2, 0);
    $row_counter = 0;
    while ($row = pg_fetch_assoc($rs2)) {
        $au_id = $row["id"];
        
        // add values and values types
        // if range the use additional fields from DB
        
        switch ($facet_definition[$f_code]["facet_type"]) {
            case "range":
                $facet_contents[$f_code]['rows'][$row_counter]['values'] = array("lower" => $row["lower"], "upper" => $row["upper"]);
                $name_to_display = $row["lower"] . "  " . t("till", $params["client_language"]) . " " . $row["upper"];
                $facet_contents[$f_code]['report'].="\n" . $name_to_display;
                break;
            case "discrete":
                $facet_contents[$f_code]['rows'][$row_counter]['values'] = array("discrete" => $row["id"]);
                $name_to_display = $row["name"];
                if (!empty($extra_row_info)) {
                    $name_to_display.="(" . $extra_row_info . ")";
                }
                break;
            case "geo":
                $facet_contents[$f_code]['rows'][$row_counter]['values'] = array("discrete" => $row["id"]);
                $name_to_display = $row["id"];
                if (!empty($extra_row_info)) {
                    $name_to_display.="(" . $extra_row_info . ")";
                }
                break;
        }
        $facet_contents[$f_code]['rows'][$row_counter]['name'] = $name_to_display;
        if (isset($direct_counts["list"]["$au_id"])) {
            $facet_contents[$f_code]['rows'][$row_counter]['direct_counts'] = $direct_counts["list"]["$au_id"];
        } else {
            $facet_contents[$f_code]['rows'][$row_counter]['direct_counts'] = "0";
        }
        $row_counter++;
    }
    return $facet_contents;
}

/*
Function: find_start_row_linear
Parameters:
* $rows of the particular facet
* $search_str the text string to position the facet

Returns:
row of facet where the search element is closest to found

*/

function find_start_row_linear($rows, $search_str)
{
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
Function: find_start_row
Parameters:
* $rows of the particular facet
* $search_str the text string to position the facet
Returns:
row of facet where the search element is closest to found
see also
<find_start_row_linear>
<find_start_row_jumping>
*/

function find_start_row($rows, $search_str)
{
    $pos = find_start_row_linear($rows, $search_str);
    if ($pos == -1) {
        $pos = find_start_row_jumping($rows, $search_str);
    }
    return $pos;
}

/*
Function:  find_start_row_jumping
Parameters:
* $rows of the particular facet
* $search_str the text string to position the facet

logics:

start search in the middle of the sorted list, then goes halv the way in the direction that is most likely.
make jumps half the distances between known position
from the start we know the interval is from 0 - end

uses the function strcasecmp


see:
http://se2.php.net/strcasecmp

CASE A:
searchstring (S) is less than current name,
go back half distance from guessed(E) position
>						 ----------------p1----------------
>                        S---------------E-----------------
>                        --------p2------------------------

CASE B:
searchstring (S) is greater then current name
, go forward half distance from guessed position (E)
>	 					----------------p1----------------
>                       ------------------------p2--------
>                       ----------------S----------------E


CASE C:
Element found exactly match


Returns:
row of facet where the search element is closest to found
*/

function find_start_row_jumping($rows, $search_str)
{
    $row_counter = 0;
    $start_position = 0;
    $search_str = mb_strtoupper($search_str, "utf-8");
    $end_position = count($rows) - 1;
    $position = floor(($end_position - $start_position) / 2);
    if (!empty($rows)) {
        // make jumps half the distances between known position
        // from the start we know the interval is from 0 - end
        $found = false;
        // loop until the difference is bigger than 1
        while (($end_position - $start_position) > 1 && $found == false) {
            $row = $rows[$position];
            $compare_to_str = substr($row["name"], 0, strlen($search_str));
            if (strcasecmp($search_str, $compare_to_str) == 0) {
                $found = true;
            } elseif (strcasecmp($search_str, $row["name"]) < 0) {
                // searchstring is less than current name,
                // go back half distance from guessed position
                $start_position = $start_position;
                $end_position = $position;
                $position = $start_position + floor(($end_position - $start_position) / 2);
                /*
                ----------------p----------------
                S---------------E----------------
                --------p------------------------
                */
            } elseif (strcasecmp($search_str, $row["name"]) > 0) {
                // searchstring is greater then current name
                //, go forward half distance from guessed position
                $start_position = $position;
                $end_position = $end_position;
                $position = $start_position + ceil(($end_position - $start_position) / 2);
                /*
                ----------------p----------------
                ------------------------p--------
                ----------------S---------------E
                */
            }
        }
    }
    return $position;
}


//***********************************************************************************************************************************************************************
/*
  function: build_xml_response
  Make the XML being send to client
  see also:
  <build_facet_c_xml>
 */
function build_xml_response($facet_c, $action_type, $duration, $start_row, $num_row,$filter_state_id=null)
{
    $xml = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
    $xml .= "<data>";
    $xml .= build_facet_c_xml($facet_c, $action_type, $start_row, $num_row);
    $xml.="<duration><![CDATA[" . $duration . "]]></duration>";
    $xml .= "</data>";
    return $xml;
}

/*
  function: build_facet_c_xml
  Make the facet content XML being send to client
  It returns the number of requested row, starting from the start_row
  It also return a scroll_to_row when text-search is being used.

 */
function build_facet_c_xml($data, $action_type, $start_row, $num_row)
{
    global $request_id;
    $xml .= "<facets>";
    if (!empty($data)) {
        $facet = $data;
        $xml .= "<facet_c>\n";
        $xml .= "<request_id>$request_id</request_id>\n";
        $xml .= "<f_code><![CDATA[" . $facet['f_code'] . "]]></f_code>\n";
        $xml .= "<report><![CDATA[" . $facet['report'] . "]]></report>\n";
        $xml .= "<report_html><![CDATA[" . $facet['report_html'] . "]]></report_html>\n";
        $xml .= "<report_xml>" . $facet['report_xml'] . "</report_xml>\n";
        $xml .= "<count_of_selections>" . $facet['count_of_selections'] . "</count_of_selections>\n";
        $xml .= "<range_interval>" . $facet['range_interval'] . "</range_interval>\n";
        $xml .= "<total_number_of_rows><![CDATA[" . $facet['total_number_of_rows'] . "]]></total_number_of_rows>\n";
        if ($action_type == "populate_text_search") {
            if ($start_row <= ($num_row / 2)) {
                $scroll_to_row = $start_row;
                $start_row = 0;
            } else {
                $scroll_to_row = $start_row;
                $start_row = $start_row - round(($num_row / 2));
            }
            $xml .= "<start_row>" . $start_row . "</start_row>";
            $xml .= "<scroll_to_row>" . $scroll_to_row . "</scroll_to_row>";
        } else {
            $xml .= "<start_row>" . $start_row . "</start_row>";
            $xml .= "<scroll_to_row>" . $start_row . "</scroll_to_row>";
        }
        $xml .= "<action_type><![CDATA[" . $action_type . "]]></action_type>";
        $xml .= "<rows>\n";
        $row_counter = 0;
        if (!empty($facet['rows'])) {
            for ($i = $start_row; $i <= $start_row + $num_row && count($facet['rows']) > $i && !empty($facet['rows'][$i]); $i++) {
                $row = $facet['rows'][$i];
                if ($row['direct_counts'] != '') {
                    $xml .= "<row>\n";
                    // make a list of values of different type
                    $xml .= "<values>\n";
                    if (!empty($row["values"])) {
                        foreach ($row["values"] as $value_type => $this_value) {
                            $xml .= "<value_item>\n";
                            $xml .= "<value_type><![CDATA[" . $value_type . "]]></value_type>\n";
                            $xml .= "<value_id><![CDATA[" . $i . "]]></value_id>\n";
                            $xml .= "<value><![CDATA[" . $this_value . "]]></value>\n";
                            $xml .=" </value_item>\n";
                        }
                    }
                    $xml .= "</values>\n";
                    $xml .= "<name><![CDATA[" . $row['name'] . "]]></name>\n";
                    $xml .= "<direct_counts><![CDATA[" . $row['direct_counts'] . "]]></direct_counts>\n";
                    $xml .= "</row>\n";
                    $row_counter++;
                }
            }
        }
        $xml .= "</rows>\n";
        $xml .= "<rows_num>" . $row_counter . "</rows_num>";
        $xml .= "</facet_c>\n";
    }
    $xml .= "</facets>";
    return $xml;
}
?>
