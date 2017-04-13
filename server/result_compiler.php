<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once(__DIR__ . "/config/environment.php");
require_once(__DIR__ . "/config/bootstrap_application.php");
require_once(__DIR__ . '/connection_helper.php');
require_once(__DIR__ . '/facet_config.php'); //
require_once(__DIR__ . '/result_query_compiler.php'); //*
require_once __DIR__ . '/facet_content_counter.php'; //*

class ResultCompiler
{
    function __construct() {
    }

    public function compile($facetsConfig, $resultConfig, $facetStateId)
    {
        $q = $this->compileQuery($facetsConfig, $resultConfig);
        if (empty($q)) {
            return ['iterator' => NULL, 'payload' => NULL];
        }          
        $data = ConnectionHelper::queryIter($q);
        $extra = $this->getExtraPayload($facetsConfig, $resultConfig, $facetStateId);
        return [ 'iterator' => $data, 'payload' => $extra ];
    }

    protected function getExtraPayload($facetsConfig, $resultConfig, $facetCacheId)
    {
        return NULL;
    }

    protected function compileQuery($facetsConfig, $resultConfig)
    {
        return ResultQueryCompiler::compileQuery($facetsConfig, $resultConfig);
    }
}

class HtmlListResultCompiler extends ResultCompiler {
    function __construct() {
        parent::__construct();
    }
}

class XmlListResultCompiler extends ResultCompiler {
    function __construct() {
        parent::__construct();
    }
}

class MapResultCompiler extends ResultCompiler {

    public $facetCode = NULL;
    function __construct() {
        parent::__construct();
        $this->facetCode = "map_result";
    }

    protected function getExtraPayload($facetsConfig, $resultConfig, $facetCacheId)
    {
        $interval = 1;
        $data = DiscreteFacetCounter::getCount($this->facetCode, $facetsConfig, $interval);
        $filtered_count = $data ? $data["list"] : NULL;
        if ($filtered_count) {
            $facetsConfigWithoutPicks = $facetsConfig->deleteUserPicks();
            $data = DiscreteFacetCounter::getCount($this->facetCode, $facetsConfigWithoutPicks, $interval);
            $un_filtered_count = $data ? $data["list"] : NULL;
        }
        return [ "filtered_count" => $filtered_count, "un_filtered_count" => $un_filtered_count ];
    }

    protected function compileQuery($facetsConfig, $resultConfig)
    {
        return MapResultQueryCompiler::compileQuery($facetsConfig, $this->facetCode);
    }
}

?>