<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

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

        $p["f_action"][0]     = "" . (string)$xml_obj->f_action->f_code;        // which facet triggeed the post
        $p["f_action"][1]     = "" . (string)$xml_obj->f_action->action_type;   // what type of action triggered to post
        $p["requested_facet"] = "" . (string)$xml_obj->requested_facet;     // Which facet wants to have new content
        $p["client_language"] = "" . (string)$xml_obj->client_language;
        
        foreach ($xml_obj->facet as $key => $element) {
            $facetCode = "" . $element->f_code;

            $p["facet_collection"][$facetCode]["facet_start_row"] = (integer)$element->facet_start_row;
            $p["facet_collection"][$facetCode]["facet_position"] = (integer)$element->facet_position;
            $p["facet_collection"][$facetCode]["facet_number_of_rows"] = (integer)$element->facet_number_of_rows;
            $p["facet_collection"][$facetCode]["facet_text_search"] = (string)$element->facet_text_search;
            
            if (!isset($element->selection_group)) {
                continue;
            }

            foreach ($element->selection_group as $temp2 => $selection_group) {
                if (isset($selection_group)) {
                    $p["facet_collection"][$facetCode]["selection_groups"][$temp2][] = $selection_group;
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

/*class FacetConfig2
{
    public $facetType = "";
    public $facetCode = "";
    public $actionType = "";
    public $language = "";
    public $facetConfigs = [];

    function __construct() {
    }
}*/


