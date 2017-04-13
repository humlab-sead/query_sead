<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once __DIR__ . '/lib/utility.php';

class ResultConfig
{
    public $request_id;             // ID of current (AJAX) request chain
    public $session_id;             // Current session ID
    public $view_type;              // Kind of result i.e. map, table, etc.
    public $client_render;
    public $aggregation_code;
    public $items = [];
    public $requestType;            // computed
    
    function __construct($property_array) {
        foreach ($property_array as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        $this->requestType = $this->view_type . $this->client_render;
    }

    public function generateCacheId($facetsConfig)
    {
        return $this->view_type . "_" . $facetsConfig->getPicksCacheId() . implode("", $this->items) . $facetsConfig->language . $this->aggregation_code;
    }

}

