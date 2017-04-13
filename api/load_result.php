<?php
/*
file: load_result.php

 This file contains functions called by the client when the result area needs to be populated. 

About:
 It handles 3 type of output:
 * list
 * REMOVED diagram
 * map
 * REMOVED piechart
NO LONGER VALID: Since this framework is made for different domain some of the function are defined differently for different domain.
NO LONGER VALID: Domain speicific function are stored in applications/xxx

Trigged by:
Javascript function <result_load_data> in <result.js>

Description:

All domain share the result list, but diagram and map function are different for each application.
It means there also specific functions used on client side. 
However the function always have the same name, but depending in which applicationName it will load different library of code.
Apart from the generic result workspace items there will be different XML-schemas for different result tabs.
Sometimes a result modules can used multiple XML-schemas.

see <facet_config.php> for function that parses XML document from client.

Facet xml post: 
http://qsead.sead.se/xml_documentation/facet_post_xml.html
http://qsead.sead.se/xml_documentation/facet_post.xsd

Result xml post:
http://qsead.sead.se/xml_documentation/result_xml_post-schema.htm
http://qsead.sead.se/xml_documentation/result_xml_post-schema.xsd

XML response for the list tab  (shared across all applications): 
http://qsead.sead.se/xml_documentation/result_response_list.xsd
http://qsead.sead.se/xml_documentation/result_response_list.html

XML post for result map:
http://qsead.sead.se/xml_documentation/result_map_post_schema.htm
http://qsead.sead.se/xml_documentation/result_map_post_schema.xsd

XML result map response for thematic mapping in map tab:
http://qsead.sead.se/xml_documentation/result_map_response_raster_overlay_schema.xsd
http://qsead.sead.se/xml_documentation/result_map_response_raster_overlay_schema.htm

Shared sequence for list, diagram, and map:
	* initiate database connection using definition in bootstrap_application.php
	* Process facetXml post using <FacetConfigDeserializer::deserialize> and store the post into a composite array
	* Remove invalid discrete selection with the function <deleteBogusPicks>
	* Process result_xml post document using <ResultConfigDeserializer::deserialize> storing the post into a composite array/objects

Sequence for list-data operation:
	* Make cache-data identifier using function derive_selection 
	* Render list using  <render_html>
	* Save the query into a table (SEAD only?)

*/

require_once __DIR__ . '/../server/connection_helper.php';
require_once __DIR__ . '/../server/lib/Cache.php';
require_once __DIR__ . '/../server/facet_config.php';
require_once __DIR__ . '/../server/cache_helper.php';
require_once __DIR__ . '/../server/result_compiler.php';
require_once(__DIR__ . "/serializers/facet_config_deserializer.php");
require_once(__DIR__ . "/serializers/result_config_deserializer.php");
require_once __DIR__ . '/serializers/result_serializer.php';
require_once(__DIR__ . "/serializers/facet_picks_serializer.php");

# TODO Move code to app service
class LoadResultHelper {

    private static $compilers = [
        "map" => "MapResultCompiler",
        "mapxml" => "MapResultCompiler",
        "listxml" => "XmlListResultCompiler",
        "listhtml" => "HtmlListResultCompiler",
        "list" => "HtmlListResultCompiler"
    ];
    private static $serializers = [
        "map" => "MapResultSerializer",
        "mapxml" => "MapResultSerializer",
        "listxml" => "XmlListResultSerializer",
        "listhtml" => "HtmlListResultSerializer",
        "list" => "HtmlListResultSerializer"
    ];
    private static $cacheables = [ "map" => false, "mapxml" => false, "listxml" => false, "listhtml" => true, "list" => true ];
    public static $isxml = [ "map" => true, "mapxml" => true, "listxml" => true, "listhtml" => false, "list" => false];

    public static function getCompiler($requestType)
    {
        $compiler_class = self::$compilers[$requestType];
        return new $compiler_class();
    }

    public static function getSerializer($requestType)
    {
        $serializer_class = self::$serializers[$requestType];
        return new $serializer_class();
    }

    public static function isCacheableResultData($requestType)
    {
        return self::$cacheables[$requestType];
    }

    public static function getFacetXml()
    {
        $facetCacheId = $_REQUEST['facet_state_id'];
        return !empty($facetCacheId) ? CacheHelper::get_facet_xml($facetCacheId) : $_REQUEST['facet_xml'];
    }

    public static function getResultXml()
    {
        $facetCacheId = $_REQUEST['facet_state_id'];
        return !empty($facetCacheId) ? CacheHelper::get_result_xml($facetCacheId) : $_REQUEST['result_xml'];
    }

    public static function cacheInputData()
    {
        $cache_id  = $_REQUEST['facet_state_id'];
        if (empty($cache_id)) {
            $cache_id = CacheIdGenerator::generateFacetStateId();
            CacheHelper::put_facet_xml($cache_id, self::getFacetXml());
        }
        CacheHelper::put_result_xml($cache_id, self::getResultXml());
        return $cache_id;
    }

    public static function putCachedResultData($type, $cache_id, $data)
    {
        CacheHelper::put_result_data($type, $cache_id, $data); 
    }

    public static function getCachedResultData($type, $cache_id)
    {
        return CacheHelper::get_result_data($type, $cache_id); 
    }
}

ConnectionHelper::openConnection();

$facetsConfig    = FacetConfigDeserializer::deserialize(LoadResultHelper::getFacetXml())->deleteBogusPicks(); 
$resultConfig    = ResultConfigDeserializer::deserialize(LoadResultHelper::getResultXml());
$resultCacheId   = $resultConfig->generateCacheId($facetsConfig);
$isCacheable     = LoadResultHelper::isCacheableResultData($resultConfig->requestType);
$serialized_data = $isCacheable ? CacheHelper::get_result_data($resultConfig->requestType, $resultCacheId) : "";
$isCached        = $isCacheable && !empty($serialized_data);

if (empty($serialized_data)) {
    $compiler = LoadResultHelper::getCompiler($resultConfig->requestType);
    $serializer = LoadResultHelper::getSerializer($resultConfig->requestType);
    $facetCacheId = LoadResultHelper::cacheInputData();
    $data = $compiler->compile($facetsConfig, $resultConfig, $facetCacheId);
    $serialized_data = $serializer->serialize($data['iterator'], $facetsConfig, $resultConfig, $facetCacheId, $data['payload']);
}

if ($isCacheable && !$isCached) {
    LoadResultHelper::putCachedResultData($resultConfig->requestType, $resultCacheId, $serialized_data);
}

$pick_data = FacetPicksSerializer::toHTML($facetsConfig->collectUserPicks());

if (!LoadResultHelper::$isxml[$resultConfig->requestType]) {
    // wrap non-xml return in CDATA tag
    $serialized_data = "<![CDATA[$serialized_data]]>";
}

// FIXME: Convert to JSON instead om XML
header("Content-Type: text/xml");
header("Character-Encoding: UTF-8");
echo "<xml>";
echo   "<response>";
echo       $serialized_data;
echo   "</response>";
echo   "<current_selections>";
echo       "<![CDATA[", $pick_data, "]]>";
echo   "</current_selections>";
echo   "<request_id>";
echo       $resultConfig->request_id;
echo   "</request_id>";
echo "</xml>";

ConnectionHelper::closeConnection();

?>