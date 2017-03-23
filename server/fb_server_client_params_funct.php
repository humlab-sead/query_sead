<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

/*
file: fb_server_client_params_funct.php
This file holds all functions to process and handle params and xml-data from client
*/

//***************************************************************************************************************************************************
/*
Function: process_symbol_xml
The xml-data is containing result information is processed and all parameters are put into an array for futher use
Returns:
$symbol_params -  An array .
*/

function process_symbol_xml($symbol_xml)
{
    $xml_object = new SimpleXMLElement($symbol_xml);
    $counter=0;
    foreach($xml_object->symbol_collection as $symbol_item_key =>$symbol_item)
    {
        foreach ($symbol_item as $tkey=>$element)
        {
            $element= (array) $element;
            $symbol_params[$counter]["symbol_key"]=(string) $element["symbol_key"];
            $symbol_params[$counter]["symbol_icon"]=(string) $element["symbol_icon"];
            $counter++;
        }
    }
    return $symbol_params;
}

//***************************************************************************************************************************************************
/*
Function: process_symbol_xml
The xml-data is containing result information is processed and all parameters are put into an array for futher use
Returns:
$symbol_params -  An array .
*/

function process_pie_chart_xml($pie_chart_xml)
{
    $pie_chart_params["request_id"]=(string) $xml_object->request_id;
    $pie_chart_params["variable"]="population_by_age";
    $pie_chart_params["year"]=2009;
    return $pie_chart_params;
}

//***************************************************************************************************************************************************
/*
Function: process_result_params
The xml-data is containing result information is processed and all parameters are put into an array for futher use
see <load_result.php> for descriptions of xml-schemas
Returns:
$result_params -  An array .
*/
function process_result_params($result_xml) {
    global $result_definition;
    $xml_object = new SimpleXMLElement($result_xml);
    $result_params["session_id"]=(string) $xml_object->session_id;
    $result_params["request_id"]=(string) $xml_object->request_id;
    
    $xml_object = $xml_object->result_input;
    $result_params["view_type"]=(string) $xml_object->view_type;
    $result_params["client_render"]=(string) $xml_object->client_render;

    $aggregation_code = (string) $xml_object->aggregation_code;

    $result_params["aggregation_code"] = $aggregation_code;
    if (!empty($aggregation_code)) {
        $result_params["items"][]=(string) $xml_object ->aggregation_code; //	the aggregation item olds holds some variables
    }
    foreach($xml_object->selected_item as $checked)
    {
        if (!empty($result_definition[(string)$checked]))
        {
            $result_params["items"][] = (string)$checked;
        }
    }
    return  (array) $result_params;
}

/*
function: process_map_xml
process the map_xml document and stores it as array
see <load_result.php> for descriptions of xml-schemas
*/

function process_map_xml($map_xml)
{
    global $result_definition;
    $xml_object = new SimpleXMLElement($map_xml);
    $map_params["map_year"]=(string) $xml_object->map_year;
    $map_params["map_result_item"]= (string )$xml_object->map_result_item;
    $map_params["map_number_of_intervals"]= (integer )$xml_object->map_number_of_intervals ?? 7;
    $map_params["color_scheme"] = !empty($xml_object->map_color_scheme) ? (string)$xml_object->map_color_scheme : "color_red";
    $map_params["classification_type"] = !empty($xml_object->map_classification_type) ? (string)$xml_object->map_classification_type : "percentiles";
    return $map_params;
}

//***************************************************************************************************************************************************
/*
Function: process_diagram_params
function to convert diagram document to an associative array

see <load_result.php> for descriptions of xml-schemas
*/
function process_diagram_params($diagram_xml){
    global $result_definition;
    $xml_object = new SimpleXMLElement($diagram_xml);
    
    //	$diagram_params["session_id"]=(string)  $xml_object->session_id;
    $diagram_params['request_id']=(string) $xml_object->request_id;
    $xml_object = $xml_object->result_input;
    $diagram_params["diagram_x_code"] =  (string) $xml_object->diagram_x_code;
    $diagram_params["group_id"] =  (string) $xml_object->group_id;
    
    $xml_y_group_object= $xml_object->y_group->diagram_y_code;
    
    foreach($xml_y_group_object as $diagram_y_code=>$test_code)
    {
        $diagram_params["y_group"][] =  (string) $test_code;
    }
    
    return  (array) $diagram_params;
}

//***************************************************************************************************************************************************
/*
Function: fb_process_params
The xml-data that contains the facet information is processed and and stored in an array for futher use.

Parameters:
$xml - xmlobject from client

_Description of the xmlobject_
- Action that the client requested (f_action) values can be "populate" or "selection change"
- Which facet that triggered the action
- Facet from which the request orginates (any of the available facet)
- View state of facet e.g. (state of facet in client-side view)
    identifier (f_code),
    position in interface/filter order,
    selections,
    requested start_row (mainly for discrete facets),
    requested end_row (mainly for discrete facets).

(start code)
<data_post>
    <f_action>
        <f_code>langen</f_code>
        <action_type>populate</action_type>
    </f_action>
    <requested_facet>langen</requested_facet>
    <request_id>1</request_id>
    <facet>
        <f_code>langen</f_code>
        <facet_position>0</facet_position>
        <facet_start_row>0</facet_start_row>
        <facet_number_of_rows>15</facet_number_of_rows>
    </facet>
</data_post>
(end code)

Returns:
  Multidimensional associative array that represents the XML
*/
function write_xml_to_file($filename, $object)
{
    $file = fopen($filename,"w");
    $data = print_r($object, TRUE);
    fwrite($file, $data);
    fclose($file);
}

// ROGER: TODO Rename to deserialize_facet_xml
function fb_process_params($xml)
{
    global $request_id;

    // $backtrace =  debug_backtrace();
    // write_xml_to_file('C:\\tmp\\qsead\\fb_process_params_backtrace_'.date('Ymd_his').'.txt', $backtrace);
    // //write_xml_to_file('C:\\tmp\\qsead\\fb_process_params_'.date('Ymd_his').'.txt', $xml);
    // try {
    // } catch (Exception $e) {
    //     echo 'Caught exception: ',  $e->getMessage(), "\n";
    // }

    write_xml_to_file('C:\\tmp\\qsead\\load_facet_configuration_'.date('Ymd_his').'.xml', $xml);
    $xml_obj=simplexml_load_string ($xml);

    $request_id = "".$xml_obj->request_id;                  // Save the id of the request. Is sent back to the client via xml without any change.
    $p["f_action"][0]="".$xml_obj->f_action->f_code;        // which facet triggeed the post
    $p["f_action"][1]="".$xml_obj->f_action->action_type;   // what type of action triggered to post
    $p["requested_facet"]="".$xml_obj->requested_facet;     // Which facet wants to have new content
    $p["client_language"]="".$xml_obj->client_language;
    
    foreach($xml_obj->facet as $key => $element)
    {
        $facet_pos=(integer) $element->facet_position;
        $f_code="".$element->f_code; // get the f_code for the facet and use that as key to the data structure of a facet
        $p["facet_collection"][$f_code]["facet_start_row"]=(integer) $element->facet_start_row; // start row of the facet, can be overwritten if it is text-search avialable
        $p["facet_collection"][$f_code]["facet_position"]=(integer)  $element->facet_position;
        $p["facet_collection"][$f_code]["facet_number_of_rows"]=(integer) $element->facet_number_of_rows;
        $p["facet_collection"][$f_code]["facet_text_search"]=   (string) $element->facet_text_search;
        
        // extract all selection of the all facets having selection in the view state
        // selection are stored in gruops to handle range-selection and multiple geo filter collections
        // Discrete selections in facets are store in a single group.
        
        if (isset($element->selection_group ))
        {
            $group_no[$f_code]= 0;
            foreach ($element->selection_group as $temp2 =>$selection_group)
            {
                // store the selection groups
                if (isset($selection_group))
                    $p["facet_collection"][$f_code]["selection_groups"][$temp2][]=  $selection_group;
            }
        }
    }
    return  $p;
}

//***************************************************************************************************************************************************
/*
function:  remove_invalid_selections
Remove all invalid selections; for instance, there could be hidden selections still being sent from the client.
They are kept at client so they can return when filters allows them to
Example, user has Län, parish filters added to interfaces
user selects Abild etc, and then selects Norrbotten
Abild is still being sent from the client but it should be ignored since it is filtered out by the Län-filter (norrbotten)
Only  discrete selection values are removed
The function queries the database
*/

function remove_invalid_selections($conn, $facet_params) {
    // also get the name of the values for the ids
    global $facet_definition;
    
    $keys_with_selections = getFacetKeysWithUserSelections($facet_params);
    
    // only for discrete since for range facets, the selection are always visible to the user.
    $selections_in_facets = getUserSelectItems($facet_params);
    if (empty($keys_with_selections)) {
        return $facet_params;
    }

    foreach ($keys_with_selections as $key => $facetKey) {
        
        if ($facet_definition[$facetKey]["facet_type"] != "discrete") {
            continue;
        }
            
        $activeKeys = getKeysOfActiveFacets($facet_params);
        
        $query = get_query_clauses($facet_params, $facetKey, $data_tables, $activeKeys);
        
        $query_column = $facet_definition[$facetKey]["id_column"];
        $query_column_name = $facet_definition[$facetKey]["name_column"];
        $current_selection = $selections_in_facets[$facetKey];
        $current_selection_values = getUserSelectDiscreteItemValues($current_selection);

        $union_clause = "";
        foreach ($current_selection_values as $sel_key => $sel_value)
            $union_clause .= ($union_clause == "" ? "" : " union ") . "select '$sel_value'::text as selection ";
        
        $tables = $query["tables"];
        $query_joins = $query["joins"];

        $q1  = " select distinct selection, $query_column_name as name_item  from ( " .
                    $union_clause .
               ") as temp_selection, $tables $query_joins " . 
               " where temp_selection.selection=$query_column::text ";
        
        $q1 .= (trim($query["where"]) != '') ? " and \n " . $query["where"] : " ";
        
        // Replace old selections with the valid ones
        $facet_params["facet_collection"][$facetKey]["selection_groups"]["selection_group"] = array();

        $i = 0;
        $rs2 = ConnectionHelper::query($conn, $q1);
        while ($row = pg_fetch_assoc($rs2)) {
            //valid selection for this facet.
            //update  and replace facet_params with this values only....
            $facet_params["facet_collection"][$facetKey]["selection_groups"]["selection_group"]["selection"][$i]["selection_type"] = "discrete";
            $facet_params["facet_collection"][$facetKey]["selection_groups"]["selection_group"]["selection"][$i]["selection_value"] = $row["selection"];
            $facet_params["facet_collection"][$facetKey]["selection_groups"]["selection_group"]["selection"][$i]["selection_text"] = $row["name_item"];
            $i++;
        }
    }
    return $facet_params;
}

/*
function: getKeysOfActiveFacets
get the list of facets in the user interface currently.
derive the order of the facet and returns a list in order the facets are arranged.
*/

function getKeysOfActiveFacets($facet_params)
{
    if (empty($facet_params["facet_collection"]))
        return NULL;
    $usedFacets = array_filter($facet_params["facet_collection"], function ($facet) { return isset($facet["facet_position"]); });
    foreach ($usedFacets as $f_code =>$facet)
        $used_facet_index[$facet["facet_position"]] = $f_code;
    ksort($used_facet_index);
    return $used_facet_index;
}

/*
function 
this function derives the selections from facet_params
*/
function getUserSelectItems($params)
{   
    if (empty($params["facet_collection"]))
        return NULL;
    $facetsWithSelection = array_filter($params["facet_collection"], function ($facet) { return !empty($facet["selection_groups"]); });
    $categorySelections = array_map(function ($facet) { return $facet["selection_groups"]; }, $facetsWithSelection);
    return $categorySelections;
}

//***************************************************************************************************************************************************
/*
function:  getUserSelectDiscreteItemValues
get the selection value from a selection group from the facet_xml-data array
*/

function getUserSelectDiscreteItemValues($current_selection_group)
{
    if (!isset($current_selection_group)) {
        return NULL;
    }
    foreach ($current_selection_group as $x => $sval) {
        foreach ($sval as $y => $selection) {
            foreach ($selection as $temp_key => $selection_element) {
                $selection_element = (array) $selection_element;
                $selection_values[] = (string) $selection_element["selection_value"];
            }
        }
    }
    return $selection_values;
}

/*
function: getFacetKeysWithUserSelections
this function derives the selection of the facet_params
*/

function getFacetKeysWithUserSelections($facet_params)
{
    $activeKeys = getKeysOfActiveFacets($facet_params);
    $userSelections = getUserSelectItems($facet_params);
    if (!empty($activeKeys) && !empty($userSelections)) {
        return array_filter($activeKeys, function ($x) use ($userSelections) { return array_key_exists($x, $userSelections); });
    }
    return [];
}

/*
function eraseUserSelectItems
this function eraseUserSelectItems the selections from facet_params
*/
function eraseUserSelectItems($params)
{
    if (empty($params["facet_collection"]))
        return $params;
    foreach ($params["facet_collection"] as $f_code => $facet)
    {
        if (!empty($facet["selection_groups"]))
            $params["facet_collection"][$f_code]["selection_groups"]="";
    }
    return $params;
}

/*
function generateUserSelectItemsCacheId
this function derives the selections from params as a string for generating caching-ids.
*/

function generateUserSelectItemsCacheId($params) {
    global $facet_definition;

    $activeKeys = getKeysOfActiveFacets($params);
    $f_selected = getUserSelectItems($params);
    if (empty($activeKeys))
        return "";

    $cache_id = "";
    foreach ($activeKeys as $pos => $facetKey) {

        if (!isset($f_selected[$facetKey]))
            continue;
        $facetType = $facet_definition[$facetKey]["facet_type"];
        foreach ($f_selected[$facetKey] as $skey => $selection_group) {

            foreach ($selection_group as $y => $selection) {
                $selection_list_discrete = array();
                foreach ($selection as $z => $item) {
                    $item = (array) $item;
                    $value = $facetKey . "_" . $item["selection_type"] . "_" . $item["selection_value"];
                    if ($facetType == "discrete") {
                        $selection_list_discrete[] = $value;
                    } else
                        $cache_id .= $value;
                }
                if ($facetType == "discrete") {
                    sort($selection_list_discrete);
                    $cache_id .= implode('_', $selection_list_discrete);
                }
            }
        }
    }
    
    return $cache_id;
}

function computeUserSelectItemCount($facet_params, $requested = false) {
    // goes through the list of facets and print the selection of each facet and also the different type of selections
    global $facet_definition, $language;
    $activeKeys = getKeysOfActiveFacets($facet_params);
    $userSelections = getUserSelectItems($facet_params);
    $count_of_selections = 0;
    if (empty($activeKeys)) {
        return "";
    }
    $facet_counter = 0;
    foreach ($activeKeys as $x => $facetKey) {
        if (isset($userSelections[$facetKey]) && ( $requested == $facetKey || $requested === false )) {
            foreach ($userSelections[$facetKey] as $y => $selection_group) {
                foreach ($selection_group as $z => $selection) {
                    $facet_type = $facet_definition[$facetKey]["facet_type"];
                    $count_of_selections  += ($facet_type == "discrete") ? count($selection) : 0;
                }
            }
        }
    }
    if ($count_of_selections == 0)
        $count_of_selections = "";
    return $count_of_selections;
}

/*
Function: generateUserSelectItemHTML
this function derives the selections from (facet) params in html-format. To be used in documentation and tooltip
It can be used for particular facet or for all facets at once.
*/

// FIXME generateUserSelectItemHTML should be client side and use generateUserSelectItemMatrix
function generateUserSelectItemHTML($facet_params,$requested=false)
{
    global $facet_definition, $language;
    $activeKeys=getKeysOfActiveFacets($facet_params);
    $f_selected=getUserSelectItems($facet_params);
    
    // goes through the ilst of facets and print the selection of each facet and also the different type of selections
    
    if (!empty($activeKeys))
    {
        $selection_html.="";
        $facet_counter=0;
        foreach ($activeKeys as $pos =>$facetKey)
        {
            if (isset($f_selected[$facetKey]) && (  $requested==$facetKey|| $requested === false )) // check that the facets has selection(s)
            {
                $rectangle_count=0;
                foreach ($f_selected[$facetKey] as $skey =>$selection_group) // dig into the gruops of selection of the facets
                {
                    foreach ($selection_group as $skey2 => $selection) // dig into the group
                    {
                        $selection_rows_html="";
                        if ($facet_definition[$facetKey]["facet_type"]=="range")
                        {
                            $selection_rows_html.="<TR><TD>";
                        }
                        foreach ( $selection as $skey3 =>  $selection_bit) // dig into the particular selection ie type and value
                        {
                            $selection_bit=(array) $selection_bit;
                            switch ($facet_definition[$facetKey]["facet_type"])
                            {
                                case "discrete":
                                    $selection_rows_html.="<TR><TD>" . $selection_bit["selection_text"] . "</TD></TR>";
                                    break;
                                case "range":
                                    $selection_rows_html.="".$selection_bit["selection_value"]." - ";
                                    break;
                            }
                        }
                        if ($facet_definition[$facetKey]["facet_type"]=="range")
                        {
                            $selection_rows_html=substr($selection_rows_html,0,-2); //remove last "-"
                            $selection_rows_html.="</TD><TD></TD></TR>";
                        }
                    }
                }
                $selection_html .= "<TD style=\"vertical-align:top\">" .
                                   "<TABLE class=\"generic_table\" ><TR><TD class=\"facet_control_bar_button\" >".$facet_definition[$facetKey]["display_title"]." </TD></TR>";
                $selection_html .= $selection_rows_html;
                $selection_html .= "</TABLE>";
                $selection_html .= "</TD>";
            }
        }
    }

    $html = <<<EOS
        <TABLE class="generic_table">
        <TR><TD><H2>Current selections<H2></TD><TR>
        <TR>
            $selection_html
        </TR>
        </TABLE>
EOS;

    return $html;
}
/*
Function: generateUserSelectItemMatrix
this function derives the selections from (facet) params in html-format. To be used in documentation and tooltip
It can be used for particular facet or for all facets at once.
*/

function generateUserSelectItemMatrix($facet_params, $requested = false) {
    
    global $facet_definition, $language;
    $activeKeys = getKeysOfActiveFacets($facet_params);
    $f_selected = getUserSelectItems($facet_params);
    
    // goes through the ilst of facets and print the selection of each facet and also the different type of selections
    if (!empty($activeKeys)) {
        $facet_counter = 0;
        foreach ($activeKeys as $pos => $facetKey) {
            if (isset($f_selected[$facetKey]) && ( $requested == $facetKey || $requested === false )) {
                $selection_rows_matrix[$facetKey]["display_title"] = $facet_definition[$facetKey]["display_title"];
                $rectangle_count = 0;
                foreach ($f_selected[$facetKey] as $skey => $selection_group) { 
                    foreach ($selection_group as $skey2 => $selection) { 
                        foreach ($selection as $skey3 => $selection_bit) {
                            $selection_bit = (array) $selection_bit;
                            switch ($facet_definition[$facetKey]["facet_type"]) {
                                case "discrete":
                                    $selection_rows_matrix[$facetKey]["selections"][] = $selection_bit;
                                    break;
                                case "range":
                                    $selection_rows_matrix[$facetKey]["selections"][0] .= $selection_bit["selection_value"] . " - ";
                                    break;
                                // case "geo":
                                //     $rectangle_count++; //actually four coordinates
                                //     $count_str = " area(s) selected";
                                //     $selection_rows_matrix[$facetKey]["selections"][0] = floor($rectangle_count / 4) . $count_str;
                                //     break;
                            }
                        }
                        if ($facet_definition[$facetKey]["facet_type"] == "range") {
                            $selection_rows_matrix[$facetKey]["selections"][0] = substr($selection_rows_matrix[$facetKey]["selections"][0], 0, -2); //remove last "-"
                        }
                    }
                }
            }
        }
    }
    return $selection_rows_matrix;
}
?>