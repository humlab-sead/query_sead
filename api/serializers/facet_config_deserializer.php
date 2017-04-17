<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once (__DIR__ . '/../../server/facet_config.php');

class FacetConfigDeserializer
{

    //***************************************************************************************************************************************************
    /*
    Function: FacetConfigDeserializer::deserialize
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
    public static function storeGlobalRequestId($current_id)
    {
        // FIXME! Side effect!!!!
        global $request_id;
        // Save the current request's id. It will be sent back to  client without change
        $request_id = "" . $current_id;
    }

    public static function deserialize($xml)
    {
        $text_filter_enabled = ConfigRegistry::getFilterByText();
        $xml_obj = simplexml_load_string($xml ?: "");
        //self::storeGlobalRequestId((string)$xml_obj->request_id);
        $facetConfigs = [];
        $inactiveConfigs = [];  // TODO: Investigate is these are used and need to be saved
        foreach ($xml_obj->facet as $element) {
            $position = (integer)$element->facet_position;
            if (!isset($position))
                continue;
            $picks = [];
            foreach ($element->selection_group ?? [] as $group) {
                // FIXME: Ensure that RANGE-values are extracted in correct order!!!
                foreach ($group->selection ?? [] as $item) {
                    $value = (array)$item;
                    if (empty($value["selection_value"])) {
                        continue;
                    }
                    $picks[] = new FacetConfigPick(
                        (string)$value["selection_type"],
                        (float)$value["selection_value"],
                        (string)$value["selection_text"] ?? "");
                }
            }
            $facetCode =(string)$element->f_code;
            $startRow = (integer)$element->facet_start_row;
            $rowCount = (integer)$element->facet_number_of_rows;
            $filter = $text_filter_enabled ? (string)$element->facet_text_search : "";
            $config = new FacetConfig2($facetCode, $position, $startRow, $rowCount, $filter, $picks);
            if (isset($position)) {
                $facetConfigs[$facetCode] = $config;
            } else {
                $inactiveConfigs[$facetCode] = $config;
            }
        }
        $requestId = (string)($xml_obj->request_id ?: ""); 
        $requestType = (string)($xml_obj->f_action->action_type ?: ""); 
        $targetCode = (string)($xml_obj->requested_facet ?: "");
        $triggerCode = (string)($xml_obj->f_action->f_code ?: "");
        $language = (string)$xml_obj->client_language ?? ""; 
        $facetsConfig = new FacetsConfig2($requestId, $language, $facetConfigs, $requestType, $targetCode, $triggerCode);
        $facetsConfig->inactiveConfigs = $inactiveConfigs;
        return $facetsConfig;
    }
}
