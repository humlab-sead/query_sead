<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once __DIR__ . '/connection_helper.php';
require_once __DIR__ . '/config/bootstrap_application.php';
require_once __DIR__ . "/lib/Cache.php";

class CacheHelper {

    private static $cache_dir = __DIR__ . "/../api/cache/";

    public static function generateFacetStateId($conn)
    {
        global $cache_seq, $application_name; 
        $cache_seq_id = $cache_seq ?? 'file_name_data_download_seq';
        $row = ConnectionHelper::queryRow($conn, "select nextval('$cache_seq_id') as cache_id;");
        $facetStateId = $application_name . $row["cache_id"];
        return $facetStateId;
    }

    public static function get_cache_content($filename)
    {
        return file_get_contents(self::$cache_dir . $filename);
    }

    public static function get_facet_xml_from_id($facet_state_id)
    {
        return self::get_cache_content("{$state_id}_facet_xml.xml");
    }

    public static function get_result_xml_from_id($result_state_id)
    {
        return self::get_cache_content("{$result_state_id}_result_xml.xml");
    }

    public static function generateFacetConfigSelectStateCacheId($facetConfig)
    {
        global $facet_definition;
        $activeKeys = FacetConfig::getKeysOfActiveFacets($facetConfig);
        $itemsSelectedByUser = FacetConfig::getItemGroupsSelectedByUser($facetConfig);
        if (empty($activeKeys)) {
            return "";
        }
        $cache_id = "";
        foreach ($activeKeys as $pos => $facetKey) {
            if (!isset($itemsSelectedByUser[$facetKey])) {
                continue;
            }
            $facetType = $facet_definition[$facetKey]["facet_type"];
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

    public static function computeFacetConfigCacheId($facetConfig)
    {
        global $filter_by_text;
        $f_code = $facetConfig["requested_facet"];
        $flist_str = implode("", FacetConfig::getKeysOfActiveFacets($facetConfig));
        $filter = $filter_by_text ? $facetConfig["facet_collection"][$f_code]["facet_text_search"] : "no_text_filter";
        return $f_code . $flist_str . self::generateFacetConfigSelectStateCacheId($facetConfig) . $facetConfig["client_language"] . $filter;
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
        return (string)$resultConfig["view_type"] ."_" . self::generateFacetConfigSelectStateCacheId($facetConfig) . 
                self::generateResultXmlSelectStateCacheId($resultXml) . $facetConfig["client_language"] . $resultConfig["aggregation_code"];
    }

    public static function Get($context_id, $cache_id)
    {
        return DataCache::Get($context_id, $cache_id);
    }

    public static function Put($context_id, $cache_id, $x, $data)
    {
        DataCache::Put($context_id, $cache_id, $x, $data);
    }
}

?>
