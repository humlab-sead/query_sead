<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once __DIR__ . '/connection_helper.php';
require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/config/bootstrap_application.php';
require_once __DIR__ . "/lib/Cache.php";

class CacheHelper {

    private static $cache_dir = __DIR__ . "/../api/cache/";
    public static $disabled = false;

    // Facet Config XML
    private static $facet_xml_context = 'facet_xml';
    public static function get_facet_xml($cache_id)
    {
        return self::Get($facet_xml_context, "{$cacheId}.xml");
    }

    public static function put_facet_xml($cacheId, $data)
    {
        self::Put(self::$facet_xml_context, "{$cacheId}.xml", $data);
    }    

    // public static function put_facet_xml_generate_cache_id($conn, $data)
    // {
    //     $cache_id = CacheIdGenerator::generateFacetStateId($conn);
    //     self::put_facet_xml($cache_id, $data);
    //     return $cache_id;
    // }    

    // Result Config XML
    private static $facet_result_xml_context = 'result_xml_';
    public static function get_result_xml($cache_id)
    {
        return self::Get($facet_result_xml_context, "{$cache_id}.xml");
    }

    public static function put_result_xml($cache_id, $data)
    {
        return self::Put(self::$facet_result_xml_context, "{$cacheId}.xml", $data);
    }

    // Facet Content
    private static $facet_content_context = 'facet_content_';
    public static function get_facet_content($cacheId)
    {
        return self::Get(self::$facet_content_context, $cacheId);
    }

    public static function put_facet_content($cacheId, $data)
    {
        return self::Put(self::$facet_content_context, $cacheId, $data);
    }

    // Result data
    private static $result_data_context = 'result_data_';
    public static function get_result_data($type, $cacheId)
    {
        return self::Get($type . '_' . self::$result_data_context, $cacheId);
    }

    public static function put_result_data($type, $cacheId, $data)
    {
        return self::Put($type . '_' . self::$result_data_context, $cacheId, $data);
    }

    // Facet category MIN/MAX
    private static $facet_min_max_context = 'facet_min_max_';
    private static $facet_min_max_cache_id = 'facet_range_data';
    public static function get_facet_min_max()
    {
        return self::Get(self::$facet_min_max_context, self::$facet_min_max_cache_id);
    }

    public static function put_facet_min_max($data)
    {
        return self::Put(self::$facet_min_max_context, self::$facet_min_max_cache_id, $data);
    }

    // Generic Get / Put
    public static function Get($contextId, $cacheId)
    {
        return DataCache::Get($contextId, $cacheId);
    }

    private static $ttl = 1500;
    public static function Put($contextId, $cacheId, $data)
    {
        return DataCache::Put($contextId, $cacheId, self::$ttl, $data);
    }
}

class CacheIdGenerator
{
    public static function generateFacetStateId($conn)
    {
        $cache_seq_id = ConfigRegistry::getCacheSeq() ?? 'file_name_data_download_seq';
        $row = ConnectionHelper::queryRow($conn, "select nextval('$cache_seq_id') as cache_id;");
        $facetStateId = ConfigRegistry::getApplicationName() . $row["cache_id"];
        return $facetStateId;
    }

    public static function generateFacetConfigSelectStateCacheId($facetConfig)
    {
        $activeKeys = FacetConfig::getCodesOfActiveFacets($facetConfig);
        $itemsSelectedByUser = FacetConfig::getItemGroupsSelectedByUser($facetConfig);
        if (empty($activeKeys)) {
            return "";
        }
        $cache_id = "";
        foreach ($activeKeys as $pos => $facetKey) {
            if (!isset($itemsSelectedByUser[$facetKey])) {
                continue;
            }
            $facet = FacetRegistry::getDefinition($facetKey);
            $facetType = $facet["facet_type"];
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

    public static function computeFacetContentCacheId($facetConfig)
    {
        $filter_by_text = ConfigRegistry::getFilterByText();
        $facetCode = $facetConfig["requested_facet"];
        $flist_str = implode("", FacetConfig::getCodesOfActiveFacets($facetConfig));
        $filter = $filter_by_text ? $facetConfig["facet_collection"][$facetCode]["facet_text_search"] : "no_text_filter";
        return $facetCode . $flist_str . CacheIdGenerator::generateFacetConfigSelectStateCacheId($facetConfig) . $facetConfig["client_language"] . $filter;
    }

    public static function generateResultXmlSelectStateCacheId($resultXml)
    {
        global $result_definition;
        $xml_object = new SimpleXMLElement($resultXml);
        $result_items_str="";
        $xml_object = $xml_object->result_input;
        foreach ($xml_object->selected_item as $checked) {
            if (!empty($result_definition[(string)$checked])) {
                $result_items_str .= (string)$checked;
            }
        }
        return (string)$result_items_str;
    }

    public static function computeResultConfigCacheId($facetConfig, $resultConfig, $resultXml)
    {
        return (string)$resultConfig["view_type"] ."_" . CacheIdGenerator::generateFacetConfigSelectStateCacheId($facetConfig) . 
                self::generateResultXmlSelectStateCacheId($resultXml) . $facetConfig["client_language"] . $resultConfig["aggregation_code"];
    }


}

?>
