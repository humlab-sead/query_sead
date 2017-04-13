<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once __DIR__ . '/connection_helper.php';
require_once __DIR__ . '/config/environment.php';
require_once __DIR__ . '/config/bootstrap_application.php';
require_once __DIR__ . "/lib/Cache.php";

class CacheHelper {

    public static $disabled = false;

    // Facet Config XML
    private static $facet_xml_context = 'facet_xml';
    public static function get_facet_xml($key)
    {
        return self::Get(self::$facet_xml_context, "{$key}.xml");
    }

    public static function put_facet_xml($key, $data)
    {
        self::Put(self::$facet_xml_context, "{$key}.xml", $data);
    }    

    // public static function put_facet_xml_generate_cache_id($data)
    // {
    //     $key = CacheIdGenerator::generateFacetStateId();
    //     self::put_facet_xml($key, $data);
    //     return $key;
    // }    

    // Result Config XML
    private static $facet_result_xml_context = 'result_xml_';
    public static function get_result_xml($key)
    {
        return self::Get(self::$facet_result_xml_context, "{$key}.xml");
    }

    public static function put_result_xml($key, $data)
    {
        return self::Put(self::$facet_result_xml_context, "{$key}.xml", $data);
    }

    // Facet Content
    private static $facet_content_context = 'facet_content_';
    public static function get_facet_content($key)
    {
        return self::Get(self::$facet_content_context, $key);
    }

    public static function put_facet_content($key, $data)
    {
        return self::Put(self::$facet_content_context, $key, $data);
    }

    // Result data
    private static $result_data_context = 'result_data_';
    public static function get_result_data($type, $key)
    {
        return self::Get($type . '_' . self::$result_data_context, $key);
    }

    public static function put_result_data($type, $key, $data)
    {
        return self::Put($type . '_' . self::$result_data_context, $key, $data);
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
    public static function Get($contextId, $key)
    {
        return DataCache::Get($contextId, $key);
    }

    private static $ttl = 1500;
    public static function Put($contextId, $key, $data)
    {
        return DataCache::Put($contextId, $key, self::$ttl, $data);
    }
}

class CacheIdGenerator
{
    public static function generateFacetStateId()
    {
        $cache_seq_id = ConfigRegistry::getCacheSeq() ?? 'file_name_data_download_seq';
        $row = ConnectionHelper::queryRow("select nextval('$cache_seq_id') as cache_id;");
        $facetStateId = ConfigRegistry::getApplicationName() . $row["cache_id"];
        return $facetStateId;
    }
}

?>
