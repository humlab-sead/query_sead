<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

/*
file: fb_server_funct.php
This file holds for handling queries and returns content for the client.
see also:

image_functions - <fb_server_image_funct.php>
parameters functions - <facet_config.php>
*/
require_once __DIR__ . '/lib/dijkstra.php';
require_once __DIR__ . '/connection_helper.php';
require_once __DIR__ . "/../applications/applicationSpecification.php";
require_once __DIR__ . "/../applications/sead/fb_def.php";
require_once __DIR__ . "/lib/SqlFormatter.php";
require_once __DIR__ . '/language/t.php';
require_once __DIR__ . '/facet_config.php';
require_once __DIR__ . '/html_render.php';
require_once __DIR__ . '/facet_content_loader.php';
require_once __DIR__ . '/query_builder.php';

/*
* Funnction: *  render_column_meta_data
* Copy definition data
*/
function render_column_meta_data($result_definition, $resultConfig, $facetConfig)
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
                        $extra_text_info = " <BR>" . t("(antal med värde)", $facetConfig["client_language"]) . " ";
                        $column_meta_data[$data_item_counter]["result_column_title"]=t($result_item["text"], $facetConfig["client_language"]) ;
                        $column_meta_data[$data_item_counter]["result_column_title_extra_info"]=$extra_text_info;
                    } else {
                        $column_meta_data[$data_item_counter]["result_column_title"]=t($result_item["text"], $facetConfig["client_language"]) ;
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
$facetConfig -  An array containing info about the current view-status.
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

function result_render_list_view_xml($conn, $facetConfig, $resultConfig, $data_link, $cache_id, $data_link_text)
{
    global $facet_definition, $result_definition, $max_result_display_rows;
    $q = get_result_data_query($facetConfig, $resultConfig). " ";
    if (empty($q)) {
        return "";
    }
    $rs = ConnectionHelper::query($conn, $q);
    $column_meta_data = render_column_meta_data($result_definition, $resultConfig, $facetConfig);
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

function result_render_list_view_html($conn, $facetConfig, $resultConfig, $data_link, $cache_id, $data_link_text)
{
    global $facet_definition, $result_definition, $max_result_display_rows;
    $q = get_result_data_query($facetConfig, $resultConfig);
    if (empty($q)) {
        return "";
    }
    $rs = ConnectionHelper::query($conn, $q);
    $tot_records = pg_num_rows($rs);
    $tot_columns = count($resultConfig["items"]);
    
    $html_table = create_result_table_header($tot_records, $tot_columns, "server/" . $data_link, "server/" .$data_link_text);

    $q = SqlFormatter::format($q, false);
    $html_table.="   <!-- BEGIN SQL -->\n";
    $html_table.="   <!-- ".SqlFormatter::format($q, false)."-->";
    $html_table.="   <!-- END SQL -->\n";
    // Draw the column headlines
    $column_meta_data=  render_column_meta_data($result_definition, $resultConfig, $facetConfig);
    $html_table.= RenderHTML::render_html_header_from_array("result_output_table", $column_meta_data);
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

// function save_facet_xml($conn, $facet_xml)
// {
//     global $application_name, $cache_seq;
//     $q = isset($cache_seq) ? "select nextval('$cache_seq') as cache_id;" : "select nextval('file_name_data_download_seq') as cache_id;";
//     $rs5 = ConnectionHelper::query($conn, $q);
//     while ($row = pg_fetch_assoc($rs5)) {
//         $facet_state_id = $application_name . $row["cache_id"];
//     }
//     file_put_contents(__DIR__ . "/../api/cache/" . $facet_state_id . "_facet_xml.xml", $facet_xml);
//     return $facet_state_id;
// }

function prepare_result_params($facetConfig, $resultConfig)
{
    // prepares params for the query builder.
    // use aggregation level from resultConfig
    // aggregation code.
    global $facet_definition, $result_definition;

    if (empty($resultConfig["items"])) {
        return NULL;
    }

    $f_code = "result_facet";
    $query_column = $facet_definition[$f_code]["id_column"];
    $group_by_str = "";
    $alias_counter = 1;
    $client_language = $facetConfig["client_language"];
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
function get_result_data_query($facetConfig, $resultConfig)
{
    $return_object = prepare_result_params($facetConfig, $resultConfig);

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
    $tmp_list = FacetConfig::getKeysOfActiveFacets($facetConfig);
    //Add result_facet as final facet
    $tmp_list[] = $f_code;
    
    $query = get_query_clauses($facetConfig, $f_code, $data_tables, $tmp_list);
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
Function: get_query_clauses
This the core of the dynamic query builder.     It's input are previous selected filters and the code of the facet that triggered the action.
It is also use an array of filter to filter by text each facet result,
Parameters:
* $paramss all facetConfig, selections, text_filter, positions of facets ie the view.state of the client)
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
    $query_builder = new QueryBuilder();
    $query_info = $query_builder->get_query_information( $join_columns, $ourMap, $f_tables, $facet_definition, $params, $f_code, $extra_tables, $f_list );
    return $query_info;
}

class RangeFacetCounter {

    public static function get_range_counts($conn, $f_code, $facetConfig, $q_interval, $direct_count_table, $direct_count_column)
    {
        global $facet_definition;
        
        $direct_counts = array();
        $combined_list = array();
        $query_table = $facet_definition[$f_code]["table"];
        $data_tables[] = $query_table;
        $data_tables[] = $direct_count_table;
        
        $f_list = FacetConfig::getKeysOfActiveFacets($facetConfig);
        
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

        $rs = ConnectionHelper::execute($conn, $q);

        while ($row = pg_fetch_assoc($rs)) {
            $facet_term = $row["facet_term"];
            $direct_counts["$facet_term"] = $row["direct_count"];
        }
        
        $combined_list["list"] = $direct_counts;
        $combined_list["sql"]= SqlFormatter::format($q, false);
        return $combined_list;
    }
}

class DiscreteFacetCounter {

    //***************************************************************************************************************************************************
    /*
    * insert an item an array
    */
    private static function insert_item_before_search_item($f_list, $requested_facet, $insert_item)
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
    private static function get_discrete_count_query2($count_facet, $facetConfig, $summarize_type = "count")
    {
        global $facet_definition;
        $f_list = FacetConfig::getKeysOfActiveFacets($facetConfig);
        if (!empty($facetConfig["requested_facet"])) {
            $requested_facet=$facetConfig["requested_facet"];
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
        
        $f_list = self::insert_item_before_search_item($f_list, $requested_facet, $count_facet);
        
        $query = get_query_clauses($facetConfig, $count_facet, $extra_tables, $f_list);

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

    public static function get_discrete_counts($conn, $f_code, $facetConfig, $payload, $direct_count_table, $direct_count_column)
    {
        global $facet_definition;
        $direct_counts = array();
        $combined_list = array();
        $data_tables[] = $direct_count_table;
        $query_table = isset($facet_definition["alias_table"]) ? $facet_definition["alias_table"] : $facet_definition[$f_code]["table"];
        $data_tables[] = $query_table;
        $f_list = FacetConfig::getKeysOfActiveFacets($facetConfig);
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
        $q = self::get_discrete_count_query2($count_facet, $facetConfig, $summarize_type);
        $max_count = 0;
        $min_count = 99999999999999999;
        $rs = ConnectionHelper::execute($conn, $q);
        while ($row = pg_fetch_assoc($rs)) {
            $facet_term = $row["facet_term"];
            $max_count = max($max_count, $row["direct_count"]);
            $min_count = min($min_count, $row["direct_count"]);
            $direct_counts["$facet_term"] = $row["direct_count"];
        }

        $combined_list["list"] = $direct_counts;
        $combined_list["sql"] = $q;
        return $combined_list;
    }
}

class RowFinder {
    /*
    Function: findIndex
    Parameters:
    * $rows of the particular facet
    * $search_str the text string to position the facet
    Returns: row of facet where the search element is closest to found
    see also
    <findExactIndex>
    <findClosestIndex>
    */

    public static function findIndex($rows, $search_str, $key = "name")
    {
        $pos = RowFinder::findExactIndex($rows, $search_str, $key);
        if ($pos == -1) {
            $pos = RowFinder::findClosestIndex($rows, $search_str, $key);
        }
        return $pos;
    }

    /*
    Function: finfindExactIndexdIndex
    Parameters:
    * $rows of the particular facet
    * $search_str the text string to position the facet
    Returns:
    row of facet where the search element is closest to found
    */

    private static function findExactIndex($rows, $search_str, $key)
    {
        $search_str = mb_strtoupper($search_str, "utf-8");
        if (isset($rows)) {
            $position = 0; 
            foreach ($rows as $row) {
                $compare_to_str = substr($row[$key], 0, strlen($search_str));
                if (strcasecmp($search_str, $compare_to_str) == 0) {
                    return $position;
                }
                $position++;
            }
        }
        return -1;
    }

    /*
    Function:  findClosestIndex
    Parameters:
    * $rows of the particular facet
    * $search_str the text string to position the facet
    */

    private static function findClosestIndex($rows, $search_str, $key)
    {
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
