<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once __DIR__ . '/../../server/result_config.php';

class ResultConfigDeserializer
{

    //***************************************************************************************************************************************************
    /*
    Function: ResultConfigDeserializer::deserialize
        Deserializes result config data
    Returns:
        <ResultConfig>
    */
    public static function deserialize($xml_text)
    {
        $xml = new SimpleXMLElement($xml_text);
        $aggregation_code = (string)$xml->result_input->aggregation_code;
        $items = empty($aggregation_code) ? [] : [ $aggregation_code ];
        foreach ($xml->result_input->selected_item as $key) {
            if (!empty(ResultDefinitionRegistry::getDefinition((string)$key)))
                $items[] = (string)$key;
        }
        return new ResultConfig ([
            "session_id"        => (string)$xml->session_id,
            "request_id"        => (string)$xml->request_id,
            "view_type"         => (string)$xml->result_input->view_type,
            "client_render"     => (string)$xml->result_input->client_render,
            "aggregation_code"  => $aggregation_code,
            "items"             => $items
        ]);
    }

}
