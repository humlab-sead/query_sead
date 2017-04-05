<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once(__DIR__ . "/config/environment.php");
require_once(__DIR__ . "/config/bootstrap_application.php");
require_once(__DIR__ . '/connection_helper.php');
require_once(__DIR__ . '/facet_config.php'); //
//require_once(__DIR__ . '/query_builder.php');
require_once(__DIR__ . '/result_query_compiler.php'); //*
require_once __DIR__ . '/facet_content_counter.php'; //*

class ResultCompiler
{
    public $conn = NULL;
    function __construct($conn) {
        $this->conn = $conn;
    }

    public function compile($facetConfig, $resultConfig, $facetStateId)
    {
        $q = $this->compileQuery($facetConfig, $resultConfig);
        if (!empty($q)) {
            $data = ConnectionHelper::queryIter($this->conn, $q);
            $extra = $this->getExtraPayload($facetConfig, $resultConfig, $facetStateId);
        }
        return [ 'iterator' => $data, 'payload' => $extra ];
    }

    protected function getExtraPayload($facetConfig, $resultConfig, $facetStateId)
    {
        return NULL;
    }

    protected function compileQuery($facetConfig, $resultConfig)
    {
        return ResultQueryCompiler::compileQuery($facetConfig, $resultConfig);
    }
}

class HtmlListResultCompiler extends ResultCompiler {
   function __construct($conn) {
       parent::__construct($conn);
   }
}

class XmlListResultCompiler extends ResultCompiler {
   function __construct($conn) {
       parent::__construct($conn);
   }
}

class MapResultCompiler extends ResultCompiler {

    public $facetCode = NULL;
    function __construct($conn) {
        parent::__construct($conn);
        $this->facetCode = "map_result";
    }

    protected function getExtraPayload($facetConfig, $resultConfig, $facetStateId)
    {
        $interval = 1;
        $data = DiscreteFacetCounter::get_discrete_counts($this->conn, $this->facetCode, $facetConfig, $interval);
        $filtered_count = $data ? $data["list"] : NULL;
        if ($filtered_count) {
            $facetConfigWithoutFilter = FacetConfig::eraseUserSelectItems($facetConfig);
            $data = DiscreteFacetCounter::get_discrete_counts($this->conn, $this->facetCode, $facetConfigWithoutFilter, $interval);
            $un_filtered_count = $data ? $data["list"] : NULL;
        }
        return array("filtered_count" => $filtered_count, "un_filtered_count" => $un_filtered_count);
    }

    protected function compileQuery($facetConfig, $resultConfig)
    {
        return MapResultQueryCompiler::compileQuery($facetConfig, $this->facetCode);
    }
}

?>