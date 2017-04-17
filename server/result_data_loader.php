<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once __DIR__ . "/config/environment.php";
require_once __DIR__ . "/config/bootstrap_application.php";
require_once __DIR__ . '/connection_helper.php';
require_once __DIR__ . '/facet_config.php';
require_once __DIR__ . '/result_sql_compiler.php';
require_once __DIR__ . '/facet_histogram_loader.php';

class ResultDataLoader
{
    function __construct() {
    }

    public function load($facetsConfig, $resultConfig, $facetStateId)
    {
        $sql = $this->compileSql($facetsConfig, $resultConfig);
        if (empty($sql)) {
            return ['iterator' => NULL, 'payload' => NULL];
        }          
        $data = ConnectionHelper::queryIter($sql);
        $extra = $this->getExtraPayload($facetsConfig, $resultConfig, $facetStateId);
        return [ 'iterator' => $data, 'payload' => $extra ];
    }

    protected function getExtraPayload($facetsConfig, $resultConfig, $facetCacheId)
    {
        return NULL;
    }

    protected function compileSql($facetsConfig, $resultConfig)
    {
        return ResultSqlQueryCompiler::compile($facetsConfig, $resultConfig);
    }
}

class MapResultDataLoader extends ResultDataLoader {

    public $facetCode = NULL;
    function __construct() {
        parent::__construct();
        $this->facetCode = "map_result";
    }

    protected function getExtraPayload($facetsConfig, $resultConfig, $facetCacheId)
    {
        $histogram_loader = new DiscreteFacetHistogramLoader();
        $interval = 1;
        $data = $histogram_loader->load($this->facetCode, $facetsConfig, $interval);
        $filtered_count = $data ? $data["list"] : NULL;
        if ($filtered_count) {
            $facetsConfigWithoutPicks = $facetsConfig->deleteUserPicks();
            $data = $histogram_loader->load($this->facetCode, $facetsConfigWithoutPicks, $interval);
            $un_filtered_count = $data ? $data["list"] : NULL;
        }
        return [ "filtered_count" => $filtered_count, "un_filtered_count" => $un_filtered_count ];
    }

    protected function compileSql($facetsConfig, $resultConfig)
    {
        return MapResultSqlQueryCompiler::compile($facetsConfig, $this->facetCode);
    }
}

?>