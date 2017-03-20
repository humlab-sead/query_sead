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
function process_result_params($result_xml){
    global $result_definition;
    $xml_object = new SimpleXMLElement($result_xml);
    $result_params["session_id"]=(string) $xml_object->session_id;
    $result_params["request_id"]=(string) $xml_object->request_id;
    
    $xml_object = $xml_object->result_input;
    $result_params["view_type"]=(string) $xml_object ->view_type;
    $result_params["client_render"]=(string) $xml_object ->client_render;
    $result_params["aggregation_code"]=(string) $xml_object ->aggregation_code;
    $result_params["items"][]=(string) $xml_object ->aggregation_code; //	the aggregation item olds holds some variables
    
    foreach($xml_object->selected_item as $checked)
    {
        if (!empty($result_definition[(string) $checked]))
        {
            $result_params["items"][] =  (string) $checked;
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
    // get the map parameters
    global $result_definition;
    $xml_object = new SimpleXMLElement($map_xml);
    $map_params["map_year"]=(string) $xml_object->map_year;
    $map_params["map_result_item"]= (string )$xml_object->map_result_item;
    
    if (isset($xml_object->map_number_of_intervals)!=0)
        $map_params["map_number_of_intervals"]= (integer )$xml_object->map_number_of_intervals;
    else
        $map_params["map_number_of_intervals"]=7;
    
    if (!empty($xml_object->map_color_scheme))
        $map_params["color_scheme"]= (string) $xml_object->map_color_scheme;
    else
        $map_params["color_scheme"]="color_red";
    
    if (!empty($xml_object->map_classification_type))
        $map_params["classification_type"]= (string) $xml_object->map_classification_type;
    else
        $map_params["classification_type"]="percentiles";
    
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
The xml-data is containing facet information is processed and all parameters are put into an array for futher use.

Parameters:
$xml - xmlobject from client

_Description of the xmlobject_
- Action the client was doing (f_action) values can be "populate" or "selection change"
- Which facet that trigged the action
- Requested facet (any of the available facet)
- View state of facet eg
identifier (f_code),
position in interface/filter order,
selections,
requested start_row (mainly for discrete facets),
requested end_row (mainly for discrete facets).

(start code)
<data_post>
<f_action><f_code>langen</f_code><action_type>populate</action_type></f_action>
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
Multidimensional associative array
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

/*
function: derive_facets_with_selection
this function derives the selection of the facet_params
*/

function derive_facets_with_selection($facet_params)
{
    $selection_facets=[];
    if (!empty($facet_params["facet_collection"]))
    {
        foreach ($facet_params["facet_collection"] as $f_code =>$facet)
        {
            if (!empty($facet["selection_groups"]))
            {
                // store groups of selections
                $f_selected[$f_code]=$facet["selection_groups"];
            }
        }
    }
    
    $f_list=derive_facet_list($facet_params);
    
    $selection_str="";
    if (!empty($f_list))
    {
        foreach ($f_list as $pos =>$facet)
        {
            if (isset($f_selected[$facet]))
            {
                $selection_facets[]=$facet;
            }
        }
    }
    
    return $selection_facets;
}

//***************************************************************************************************************************************************
/*
function:  remove_invalid_selections
Remove all invalid selections for instance there could be hidden selection that are still being send from the client.
They are kept at client so the will return when filters allows them to be shown.
Example, user has Län, parish filters added to interfaces
user selects Abild etc, and then selects Norrbotten
Abild is still being sent from the client but it should not affect since it is filtered out by the Län-filter (norrbotten)
The function only removes discrete selections values
The function querties the database
*/

function remove_invalid_selections($conn, $facet_params) {
    // also get the name of the values for the ids
    global $facet_definition;
    
    $facet_with_selections = derive_facets_with_selection($facet_params);
    
    // only for discrete since for range facets, the selection are always visible to the user.
    $selections_in_facets = derive_selections($facet_params);
    if (!empty($facet_with_selections))
    foreach ($facet_with_selections as $key => $f_code) {
        
        if ($facet_definition[$f_code]["facet_type"] == "discrete") {
            
            $f_list = derive_facet_list($facet_params);
            
            $query = get_query_clauses($facet_params, $f_code, $data_tables, $f_list);
            
            $query_column = $facet_definition[$f_code]["id_column"];
            $query_column_name = $facet_definition[$f_code]["name_column"];
            
            // only use the one with discrete facets selection
            $current_selection = $selections_in_facets[$f_code];
            
            $current_selection_values = get_discrete_selection_values($current_selection);
            $union_str = "";
            foreach ($current_selection_values as $sel_key => $sel_value)
            $union_str.= " select '$sel_value'::text  as selection      union";
            
            // remove  last "union"
            $union_str = substr($union_str, 0, -6);
            
            $tables = $query["tables"];
            $q1 = " select distinct selection, $query_column_name as name_item  from ( ";
            $q1.= $union_str . ") as temp_selection, " . $tables . " ".$query["joins"]." where temp_selection.selection=$query_column::text ";
            
            // ----- BEGINNING of common part of all queries -----//
            $common_part = get_common_query($query);
            $q1.=$common_part;
            // ----- END of common part of all queries -----//
            $q1.= " ";
            
            // delete all selections
            // add all valid ones
            $facet_params["facet_collection"][$f_code]["selection_groups"]["selection_group"] = array();
            $i = 0;
            if (($rs2 = pg_exec($conn, $q1)) <= 0) {
                echo "Error: cannot execute query2. get valid selection only  $q1  \n";
                pg_close($conn);
                exit;
            }
            while ($row = pg_fetch_assoc($rs2)) {
                //valid selection for this facet.
                //update  and replace facet_params with this values only....
                $facet_params["facet_collection"][$f_code]["selection_groups"]["selection_group"]["selection"][$i]["selection_type"] = "discrete";
                $facet_params["facet_collection"][$f_code]["selection_groups"]["selection_group"]["selection"][$i]["selection_value"] = $row["selection"];
                $facet_params["facet_collection"][$f_code]["selection_groups"]["selection_group"]["selection"][$i]["selection_text"] = $row["name_item"];
                $i++;
            }
        }
    }
    
    return $facet_params;
}

/*
function: derive_facet_list
get the list of facets in the user interface currently.
derive the order of the facet and returns a list in order the facets are arranged.
*/

function derive_facet_list($facet_params)
{
    if (!empty($facet_params["facet_collection"]))
    {
        foreach ($facet_params["facet_collection"] as $f_code =>$facet)
        {
            if (isset($facet["facet_position"]))
            {
                $position=$facet["facet_position"];
                $f_list[$position]=$f_code;
            }
        }
        ksort($f_list);
    }
    return $f_list;
}


/*
function derive_selections
this function derives the selections from facet_params
*/
function derive_selections($params)
{
    if (!empty($params["facet_collection"]))
    {
        foreach ($params["facet_collection"] as $f_code =>$facet)
        {
            if (!empty($facet["selection_groups"]))
            {
                // store groups of selections
                $f_selected[$f_code]=$facet["selection_groups"];
            }
        }
    }
    return $f_selected;
}


/*
function erase_selections
this function erase_selections the selections from facet_params
*/
function erase_selections($params)
{
    if (!empty($params["facet_collection"]))
    {
        foreach ($params["facet_collection"] as $f_code =>$facet)
        {
            if (!empty($facet["selection_groups"]))
            {
                //erase'em
                $params["facet_collection"][$f_code]["selection_groups"]="";
            }
        }
    }
    return $params;
}

/*
function derive_selections_string
this function derives the selections from params as a string for generating caching-ids.
*/

function derive_selections_string($params) {
    global $facet_definition;
    $f_list = derive_facet_list($params);
    $f_selected = derive_selections($params);
    $selection_str = "";
    if (!empty($f_list)) {
        foreach ($f_list as $pos => $facet) {
            if (isset($f_selected[$facet])) {
                foreach ($f_selected[$facet] as $skey => $selection_group) {
                    foreach ($selection_group as $skey2 => $selection) {
                        // ROGER: $selection_list_discrete = "";
                        $selection_list_discrete = array();
                        foreach ($selection as $skey3 => $selection_bit) {
                            $selection_bit = (array) $selection_bit;
                            if ($facet_definition[$facet]["facet_type"] == "discrete") {
                                // to be sorted to improve the caching indexing since the order of selection within a discrete facet does not matter and the client are ordering the selection in the order they are clicked.
                                $selection_list_discrete[] = $facet . "_" . $selection_bit["selection_type"] . "_" . $selection_bit["selection_value"];
                            } else
                                $selection_str.=$facet . "_" . $selection_bit["selection_type"] . "_" . $selection_bit["selection_value"];
                        }
                        // sort selection for discrete ones
                        if ($facet_definition[$facet]["facet_type"] == "discrete") {
                            sort($selection_list_discrete); // sort the selection so the order the user selected the discrete values should not matter.
                            foreach ($selection_list_discrete as $sort_key => $sort_item) {
                                // make string of the sorted selection
                                $selection_str.=$sort_item;
                            }
                        }
                    }
                }
            }
        }
    }
    
    return $selection_str;
}

function derive_count_of_selection($facet_params, $requested = false) {
    // goes through the ilst of facets and print the selection of each facet and also the different type of selections
    
    global $facet_definition, $language;
    $f_list = derive_facet_list($facet_params);
    $f_selected = derive_selections($facet_params);
    $count_of_selections = 0;
    
    if (empty($f_list)) {
        return "";
    }
    $facet_counter = 0;
    foreach ($f_list as $pos => $facet) {
        
        if (isset($f_selected[$facet]) && ( $requested == $facet || $requested === false )) { // check that the facets has selection(s)
            foreach ($f_selected[$facet] as $skey => $selection_group) { // dig into the gruops of selection of the facets
                foreach ($selection_group as $skey2 => $selection) { // dig into the group
                    foreach ($selection as $skey3 => $selection_bit) { // dig into the particular selection ie type and value
                        $selection_bit = (array) $selection_bit;
                        
                        switch ($facet_definition[$facet]["facet_type"]) {
                            case "discrete": // many rows
                                $count_of_selections++;
                                break;
                            case "range":  // 0  - 500 // two columns
                                break;
                            case "geo": // there are few element representing one rectangle, the id of the rectangle and the 4 coordinates
                                $count_of_selections = $count_of_selections + 0.2;
                                break;
                        }
                    }
                }
            }
        }
    }

    if ($count_of_selections == 0)
        $count_of_selections = "";

    return $count_of_selections;
}

/*
Function: derive_selections_to_html
this function derives the selections from (facet) params in html-format. To be used in documentation and tooltip
It can be used for particular facet or for all facets at once.
*/

function derive_selections_to_html($facet_params,$requested=false)
{
    global $facet_definition, $language;
    $f_list=derive_facet_list($facet_params);
    $f_selected=derive_selections($facet_params);
    
    // goes through the ilst of facets and print the selection of each facet and also the different type of selections
    
    if (!empty($f_list))
    {
        $selection_html.="<TABLE class=\"generic_table\">";
        $selection_html.="<TR><TD><H2>".t("Aktuella val",$facet_params["client_language"])."<H2></TD><TR>";
        $selection_html.="<TR>";
        $facet_counter=0;
        foreach ($f_list as $pos =>$facet)
        {
            if (isset($f_selected[$facet]) && (  $requested==$facet|| $requested === false )) // check that the facets has selection(s)
            {
                $selection_html.="<TD style=\"vertical-align:top\">"; // next column
                $selection_html.="<TABLE class=\"generic_table\" ><TR><TD class=\"facet_control_bar_button\" >".$facet_definition[$facet]["display_title"]." </TD></TR>";
                $rectangle_count=0;
                foreach ($f_selected[$facet] as $skey =>$selection_group) // dig into the gruops of selection of the facets
                {
                    foreach ($selection_group as $skey2 => $selection) // dig into the group
                    {
                        $selection_rows_html="";
                        if ($facet_definition[$facet]["facet_type"]=="range")
                        {
                            $selection_rows_html.="<TR><TD>";
                        }
                        
                        foreach ( $selection as $skey3 =>  $selection_bit) // dig into the particular selection ie type and value
                        {
                            $selection_bit=(array) $selection_bit;
                            
                            switch ($facet_definition[$facet]["facet_type"])
                            {
                                case "discrete":
                                    // many rows
                                    $selection_rows_html.="<TR><TD>".$selection_bit["selection_text"]."</TD></TR>";
                                    break;
                                case "range":
                                    // 0  - 500
                                    // two columns
                                    $selection_rows_html.="".$selection_bit["selection_value"]." - ";
                                    break;
                                case "geo":
                                    // one row
                                    $rectangle_count++; //actually four coordinates
                                    $count_str=  " område(n) valda";
                                    $selection_rows_html="<TR><TD>".(floor($rectangle_count/4)).$count_str ."  </TD></TR>";
                                    break;
                            }
                            
                        }
                        
                        if ($facet_definition[$facet]["facet_type"]=="range")
                        {
                            $selection_rows_html=substr($selection_rows_html,0,-2); //remove last "-"
                            $selection_rows_html.="</TD><TD></TD></TR>";
                        }
                    }
                }
                $selection_html.=$selection_rows_html;
                $selection_html.="</TABLE>";
                $selection_html.="</TD>";
            }
        }
        $selection_html.="</TR>";
        $selection_html.="</TABLE>";
    }

    return $selection_html;
}
/*
Function: derive_selections_to_matrix
this function derives the selections from (facet) params in html-format. To be used in documentation and tooltip
It can be used for particular facet or for all facets at once.
*/

function derive_selections_to_matrix($facet_params, $requested = false) {
    
    global $facet_definition, $language;
    $f_list = derive_facet_list($facet_params);
    $f_selected = derive_selections($facet_params);
    
    // goes through the ilst of facets and print the selection of each facet and also the different type of selections
    if (!empty($f_list)) {
        $facet_counter = 0;
        foreach ($f_list as $pos => $facet) {
            //print_r($facet);
            if (isset($f_selected[$facet]) && ( $requested == $facet || $requested === false )) { // check that the facets has selection(s)
                $selection_rows_matrix[$facet]["display_title"] = $facet_definition[$facet]["display_title"];
                $rectangle_count = 0;
                foreach ($f_selected[$facet] as $skey => $selection_group) { // dig into the gruops of selection of the facets
                    foreach ($selection_group as $skey2 => $selection) { // dig into the group
                        //$selection_rows_html = "";
                        foreach ($selection as $skey3 => $selection_bit) { // dig into the particular selection ie type and value
                            // $dicrete_selection_counter++;
                            $selection_bit = (array) $selection_bit;
                            
                            switch ($facet_definition[$facet]["facet_type"]) {
                                case "discrete":
                                    // many rows
                                    $selection_rows_matrix[$facet]["selections"][] = $selection_bit;
                                    // $dicrete_selection_counter++;
                                    break;
                                case "range":
                                    // 0  - 500
                                    // two columns
                                    $selection_rows_matrix[$facet]["selections"][0].=$selection_bit["selection_value"] . " - ";
                                    break;
                                case "geo":
                                    // one row
                                    $rectangle_count++; //actually four coordinates
                                    $count_str = " område(n) valda";
                                    $selection_rows_matrix[$facet]["selections"][0] = floor($rectangle_count / 4) . $count_str;
                                    break;
                            }
                        }
                        if ($facet_definition[$facet]["facet_type"] == "range") {
                            $selection_rows_matrix[$facet]["selections"][0] = substr($selection_rows_matrix[$facet]["selections"][0], 0, -2); //remove last "-"
                        }
                    }
                }
            }
        }
    }
    return $selection_rows_matrix;
}
?>