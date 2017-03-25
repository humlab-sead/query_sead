<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

/*
file: fb_server_client_params_funct.php
This file holds all functions to process and handle params and xml-data from client
*/

class ResultConfigDeserializer
{

    //***************************************************************************************************************************************************
    /*
    Function: ResultConfigDeserializer::deserializeMapSymbolConfig
    The xml-data is containing result information is processed and all parameters are put into an array for futher use
    Returns:
    $symbolConfig -  An array .
    */

    public static function deserializeMapSymbolConfig($symbol_xml)
    {
        $xml_object = new SimpleXMLElement($symbol_xml);
        $counter=0;
        foreach ($xml_object->symbol_collection as $symbol_item_key => $symbol_item) {
            foreach ($symbol_item as $tkey => $element) {
                $element= (array) $element;
                $symbolConfig[$counter]["symbol_key"] = (string)$element["symbol_key"];
                $symbolConfig[$counter]["symbol_icon"] = (string)$element["symbol_icon"];
                $counter++;
            }
        }
        return $symbolConfig;
    }

    //***************************************************************************************************************************************************
    /*
    Function: ResultConfigDeserializer::deserializeResultConfig
    The xml-data is containing result information is processed and all parameters are put into an array for futher use
    see <load_result.php> for descriptions of xml-schemas
    Returns:
    $resultConfig -  An array .
    */
    public static function deserializeResultConfig($result_xml)
    {
        global $result_definition;

        $xml_object = new SimpleXMLElement($result_xml);
        $resultConfig["session_id"]=(string) $xml_object->session_id;
        $resultConfig["request_id"]=(string) $xml_object->request_id;
        
        $xml_object = $xml_object->result_input;

        $resultConfig["view_type"]=(string) $xml_object->view_type;
        $resultConfig["client_render"]=(string) $xml_object->client_render;

        $aggregation_code = (string)$xml_object->aggregation_code;

        $resultConfig["aggregation_code"] = $aggregation_code;
        if (!empty($aggregation_code)) {
            $resultConfig["items"][] = (string)$xml_object->aggregation_code; //	the aggregation item olds holds some variables
        }
        // FIXME: Is there ever selected items in "ResultConfig"???
        foreach ($xml_object->selected_item as $checked) {
            if (!empty($result_definition[(string)$checked])) {
                $resultConfig["items"][] = (string)$checked;
            }
        }
        return  (array) $resultConfig;
    }

    /*
    function: ResultConfigDeserializer::deserializeMapConfig
    process the map_xml document and stores it as array
    see <load_result.php> for descriptions of xml-schemas
    */

    public static function deserializeMapConfig($map_xml)
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
    Function: ResultConfigDeserializer::deserializeDiagramConfig
    function to convert diagram document to an associative array

    see <load_result.php> for descriptions of xml-schemas
    */
    public static function deserializeDiagramConfig($diagram_xml)
    {
        global $result_definition;
        $xml_object = new SimpleXMLElement($diagram_xml);
        
        $diagram_params['request_id']=(string) $xml_object->request_id;
        $xml_object = $xml_object->result_input;
        $diagram_params["diagram_x_code"] = (string) $xml_object->diagram_x_code;
        $diagram_params["group_id"] = (string) $xml_object->group_id;
        
        $xml_y_group_object = $xml_object->y_group->diagram_y_code;
        
        foreach ($xml_y_group_object as $diagram_y_code => $test_code) {
            $diagram_params["y_group"][] = (string)$test_code;
        }
        
        return (array)$diagram_params;
    }
}

class FacetConfigDeserializer
{

    //***************************************************************************************************************************************************
    /*
    Function: FacetConfigDeserializer::deserializeFacetConfig
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

    public static function deserializeFacetConfig($xml)
    {
        $xml_obj = simplexml_load_string($xml);

        self::storeGlobalRequestId((string)$xml_obj->request_id);

        $p["f_action"][0]="".$xml_obj->f_action->f_code;        // which facet triggeed the post
        $p["f_action"][1]="".$xml_obj->f_action->action_type;   // what type of action triggered to post
        $p["requested_facet"]="".$xml_obj->requested_facet;     // Which facet wants to have new content
        $p["client_language"]="".$xml_obj->client_language;
        
        foreach ($xml_obj->facet as $key => $element) {
            $facet_pos = (integer)$element->facet_position;
            $f_code = "" . $element->f_code;

            $p["facet_collection"][$f_code]["facet_start_row"] = (integer)$element->facet_start_row;
            $p["facet_collection"][$f_code]["facet_position"] = (integer)$element->facet_position;
            $p["facet_collection"][$f_code]["facet_number_of_rows"] = (integer)$element->facet_number_of_rows;
            $p["facet_collection"][$f_code]["facet_text_search"] = (string)$element->facet_text_search;
            
            if (!isset($element->selection_group)) {
                continue;
            }

            foreach ($element->selection_group as $temp2 => $selection_group) {
                if (isset($selection_group)) {
                    $p["facet_collection"][$f_code]["selection_groups"][$temp2][] = $selection_group;
                }
            }
        }
        return  $p;
    }

    public static function storeGlobalRequestId($current_id)
    {
        // FIXME! Side effect!!!!
        global $request_id;
        // Save the current request's id. It will be sent back to  client without change
        $request_id = "" . $current_id;
    }
}

class FacetConfig
{

    //***************************************************************************************************************************************************
    /*
    function:  FacetConfig::removeInvalidUserSelections
    Removes invalid selections e.g. hidden selections still being sent from the client.
    The client keep then since they can be visible when the filters changes
    This is only applicable for discrete facets (range facet selection are always visible)

    class FacetConfig
    */

    public static function removeInvalidUserSelections($conn, $facetConfig)
    {
        global $facet_definition;
        
        $keys_with_selections = self::getFacetKeysWithUserSelections($facetConfig);
        $itemsSelectedByUser = self::getItemGroupsSelectedByUser($facetConfig);
        if (empty($keys_with_selections)) {
            return $facetConfig;
        }

        foreach ($keys_with_selections as $key => $facetKey) {
            if ($facet_definition[$facetKey]["facet_type"] != "discrete") {
                continue;
            }
            $activeKeys = self::getKeysOfActiveFacets($facetConfig);
            
            $query = get_query_clauses($facetConfig, $facetKey, $data_tables, $activeKeys);
            
            $query_column = $facet_definition[$facetKey]["id_column"];
            $query_column_name = $facet_definition[$facetKey]["name_column"];
            $current_selection = $itemsSelectedByUser[$facetKey];
            $current_selection_values = self::getDiscreteValuesSelectedByUser($current_selection);
            $union_clause = implode(" union ", array_map(function ($x) {
                return  "select '$x'::text as selected_value ";
            }, $current_selection_values));
            $tables = $query["tables"];
            $query_joins = $query["joins"];
            $criteria_clause = (trim($query["where"]) != '') ? " and \n " . $query["where"] : " ";

            $q1 =<<<EOS
            select distinct selected_value, $query_column_name as name_item
            from (
                $union_clause
            ) as x,
            $tables $query_joins
            where x.selected_value = $query_column::text
                $criteria_clause
EOS;
            // Replace existing data with result of query
            $group = array();
            $rs2 = ConnectionHelper::query($conn, $q1);
            while ($row = pg_fetch_assoc($rs2)) {
                $group["selection2"][] = [ "selection_type" => "discrete", "selection_value" => $row["selected_value"], "selection_text" => $row["name_item"] ];
            }
            $facetConfig["facet_collection"][$facetKey]["selection_groups"]["selection_group"] = $group;
        }
        return $facetConfig;
    }

    /*
    function: FacetConfig::getKeysOfActiveFacets
    get the list of facets in the user interface currently.
    derive the order of the facet and returns a list in order the facets are arranged.
    */

    public static function getKeysOfActiveFacets($facetConfig)
    {
        if (empty($facetConfig["facet_collection"])) {
            return null;
        }
        $usedFacets = array_filter($facetConfig["facet_collection"], function ($facet) {
            return isset($facet["facet_position"]);
        });
        foreach ($usedFacets as $f_code => $facet) {
            $used_facet_index[$facet["facet_position"]] = $f_code;
        }
        ksort($used_facet_index);
        return $used_facet_index;
    }

    /*
    function getItemGroupsSelectedByUser
    Extracts user selections from a facetConfig
    */
    public static function getItemGroupsSelectedByUser($facetConfig)
    {
        if (empty($facetConfig["facet_collection"])) {
            return null;
        }
        $facetsWithSelection = array_filter($facetConfig["facet_collection"], function ($facet) {
            return !empty($facet["selection_groups"]);
        });
        $itemGroups = array_map(function ($facet) {
            return $facet["selection_groups"];
        }, $facetsWithSelection);
        return $itemGroups;
    }

    //***************************************************************************************************************************************************
    /*
    function:  FacetConfig::getDiscreteValuesSelectedByUser
    get the selection value from a selection group from the facet_xml-data array
    NOTE! Selected item are not deserialize - they are stored in an XML-object
    */

    private static function getDiscreteValuesSelectedByUser($xmlArray)
    {
        if (!isset($xmlArray)) {
            return null;
        }
        foreach ($xmlArray as $x => $xmlGroups) {
            foreach ($xmlGroups as $y => $xmlGroup) {
                //$items = array_map(function($x) { return ((array)$x)["selection_value"]; }, (array)$xmlGroup);
                foreach ($xmlGroup as $selections) {
                    $items[] = (string)((array)$selections)["selection_value"];
                }
            }
        }
        return $items;
    }

    /*
    function: FacetConfig:::getFacetKeysWithUserSelections
    this function derives the selection of the facetConfig
    */

    private static function getFacetKeysWithUserSelections($facetConfig)
    {
        $activeKeys = FacetConfig::getKeysOfActiveFacets($facetConfig);
        $itemsSelectedByUser = FacetConfig::getItemGroupsSelectedByUser($facetConfig);
        if (!empty($activeKeys) && !empty($itemsSelectedByUser)) {
            return array_filter($activeKeys, function ($x) use ($itemsSelectedByUser) {
                return array_key_exists($x, $itemsSelectedByUser);
            });
        }
        return [];
    }

    /*
    function eraseUserSelectItems
    this function eraseUserSelectItems the selections from facetConfig
    */
    public static function eraseUserSelectItems($facetConfig)
    {
        if (empty($facetConfig["facet_collection"])) {
            return $facetConfig;
        }
        foreach ($facetConfig["facet_collection"] as $f_code => $facet) {
            if (!empty($facet["selection_groups"])) {
                $facetConfig["facet_collection"][$f_code]["selection_groups"] = "";
            }
        }
        return $facetConfig;
    }

    /*
    function generateUserSelectItemsCacheId
    this function derives the selections from params as a string for generating caching-ids.
    */

    public static function generateUserSelectItemsCacheId($facetConfig)
    {
        global $facet_definition;

        $activeKeys = self::getKeysOfActiveFacets($facetConfig);
        $itemsSelectedByUser = self::getItemGroupsSelectedByUser($facetConfig);
        if (empty($activeKeys)) {
            return "";
        }

        $cache_id = "";
        foreach ($activeKeys as $pos => $facetKey) {
            if (!isset($itemsSelectedByUser[$facetKey])) {
                continue;
            }
            $facetType = $facet_definition[$facetKey]["facet_type"];
            foreach ($itemsSelectedByUser[$facetKey] as $skey => $selection_group) {
                foreach ($selection_group as $y => $selection) {
                    $selection_list_discrete = array();
                    foreach ($selection as $z => $item) {
                        $item = (array) $item;
                        $value = $facetKey . "_" . $item["selection_type"] . "_" . $item["selection_value"];
                        if ($facetType == "discrete") {
                            $selection_list_discrete[] = $value;
                        } else {
                            $cache_id .= $value;
                        }
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

    public static function computeUserSelectItemCount($facetConfig, $requested = false)
    {
        // goes through the list of facets and print the selection of each facet and also the different type of selections
        global $facet_definition, $language;
        $activeKeys = FacetConfig::getKeysOfActiveFacets($facetConfig);
        $itemsSelectedByUser = FacetConfig::getItemGroupsSelectedByUser($facetConfig);
        $count_of_selections = 0;
        if (empty($activeKeys)) {
            return "";
        }
        $facet_counter = 0;
        foreach ($activeKeys as $x => $facetKey) {
            if (isset($itemsSelectedByUser[$facetKey]) && ( $requested == $facetKey || $requested === false )) {
                foreach ($itemsSelectedByUser[$facetKey] as $y => $selection_group) {
                    foreach ($selection_group as $z => $selection) {
                        $facet_type = $facet_definition[$facetKey]["facet_type"];
                        $count_of_selections += ($facet_type == "discrete") ? count($selection) : 0;
                    }
                }
            }
        }
        if ($count_of_selections == 0) {
            $count_of_selections = "";
        }
        return $count_of_selections;
    }

    /*
    Function: FacetConfig::generateUserSelectItemHTML
    this function derives the selections from (facet) params in html-format. To be used in documentation and tooltip
    It can be used for particular facet or for all facets at once.
    */

    // FIXME FacetConfig::generateUserSelectItemHTML should be client side and use FacetConfig::generateUserSelectItemMatrix
    public static function generateUserSelectItemHTML($facetConfig, $requested = false)
    {
        global $facet_definition, $language;
        $activeKeys = FacetConfig::getKeysOfActiveFacets($facetConfig);
        $itemsSelectedByUser = FacetConfig::getItemGroupsSelectedByUser($facetConfig);
        
        // goes through the ilst of facets and print the selection of each facet and also the different type of selections
        
        if (!empty($activeKeys)) {
            $selection_html.="";
            $facet_counter=0;
            foreach ($activeKeys as $pos => $facetKey) {
                if (isset($itemsSelectedByUser[$facetKey]) && (  $requested==$facetKey|| $requested === false )) { // check that the facets has selection(s)
                    $rectangle_count=0;
                    foreach ($itemsSelectedByUser[$facetKey] as $skey => $selection_group) { // dig into the gruops of selection of the facets
                        foreach ($selection_group as $skey2 => $selection) { // dig into the group
                            $selection_rows_html="";
                            if ($facet_definition[$facetKey]["facet_type"]=="range") {
                                $selection_rows_html.="<TR><TD>";
                            }
                            foreach ($selection as $skey3 => $value) { // dig into the particular selection ie type and value
                                $value=(array) $value;
                                switch ($facet_definition[$facetKey]["facet_type"]) {
                                    case "discrete":
                                        $selection_rows_html.="<TR><TD>" . $value["selection_text"] . "</TD></TR>";
                                        break;
                                    case "range":
                                        $selection_rows_html.="".$value["selection_value"]." - ";
                                        break;
                                }
                            }
                            if ($facet_definition[$facetKey]["facet_type"]=="range") {
                                $selection_rows_html=substr($selection_rows_html, 0, -2); //remove last "-"
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
    Function: FacetConfig::generateUserSelectItemMatrix
    this function derives the selections from (facet) params in html-format. To be used in documentation and tooltip
    It can be used for particular facet or for all facets at once.
    */

    public static function generateUserSelectItemMatrix($facetConfig, $requested = false)
    {
        
        global $facet_definition, $language;
        $activeKeys = FacetConfig::getKeysOfActiveFacets($facetConfig);
        $itemsSelectedByUser = FacetConfig::getItemGroupsSelectedByUser($facetConfig);
        
        // goes through the facet list and stores the selection of each (type of) facet
        if (empty($activeKeys)) {
            return null;
        }
        $facet_counter = 0;
        foreach ($activeKeys as $pos => $facetKey) {
            $applicable = isset($itemsSelectedByUser[$facetKey]) && ($requested == $facetKey || $requested === false);
            if (!$applicable) {
                continue;
            }
            $facetType = $facet_definition[$facetKey]["facet_type"];
            $selection_rows_matrix[$facetKey]["display_title"] = $facet_definition[$facetKey]["display_title"];
            foreach ($itemsSelectedByUser[$facetKey] as $skey => $selection_group) {
                foreach ($selection_group as $skey2 => $selection) {
                    // if ($facetType == "range")
                    //     $selection_rows_matrix[$facetKey]["selections"][0] = implode(" - ", $selection);
                    // else if ($facetType == "discrete")
                    //     foreach ($selection as $value)
                    //         $selection_rows_matrix[$facetKey]["selections"][] = $value;

                    foreach ($selection as $value) {
                        $value = (array)$value;
                        switch ($facetType) {
                            case "discrete":
                                $selection_rows_matrix[$facetKey]["selections"][] = $value;
                                break;
                            case "range":
                                $selection_rows_matrix[$facetKey]["selections"][0] .= $value["selection_value"] . " - ";
                                break;
                        }
                    }
                    if ($facet_definition[$facetKey]["facet_type"] == "range") {
                        $selection_rows_matrix[$facetKey]["selections"][0] = substr($selection_rows_matrix[$facetKey]["selections"][0], 0, -2); //remove last "-"
                    }
                }
            }
        }
        return $selection_rows_matrix;
    }
}
