<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

class ResultConfigDeserializer
{

    //***************************************************************************************************************************************************
    /*
    Function: ResultConfigDeserializer::deserializeMapSymbolConfig
    The xml-data is containing result information is processed and all parameters are put into an array for futher use
    Returns:
    $symbolConfig -  An array .
    */

    // ROGER: REMOVED NOT USED
    // public static function deserializeMapSymbolConfig($symbol_xml)
    // {
    //     $xml_object = new SimpleXMLElement($symbol_xml);
    //     $counter=0;
    //     foreach ($xml_object->symbol_collection as $symbol_item_key => $symbol_item) {
    //         foreach ($symbol_item as $tkey => $element) {
    //             $element= (array) $element;
    //             $symbolConfig[$counter]["symbol_key"] = (string)$element["symbol_key"];
    //             $symbolConfig[$counter]["symbol_icon"] = (string)$element["symbol_icon"];
    //             $counter++;
    //         }
    //     }
    //     return $symbolConfig;
    // }

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
        $resultConfig["session_id"]=(string)$xml_object->session_id;
        $resultConfig["request_id"]=(string)$xml_object->request_id;
        
        $xml_object = $xml_object->result_input;

        $resultConfig["view_type"]=(string)$xml_object->view_type;
        $resultConfig["client_render"]=(string)$xml_object->client_render;

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
