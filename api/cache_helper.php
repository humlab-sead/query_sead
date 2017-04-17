<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

$serverRoot = __DIR__ . '/../server';

require_once $serverRoot . '/connection_helper.php';
require_once $serverRoot . '/config/environment.php';
require_once $serverRoot . '/config/bootstrap_application.php';
require_once $serverRoot . "/lib/Cache.php";

DataCache::setStore(__DIR__ . '/cache/');

class CacheKey {
    public const facet_xml_context = 'facet_xml';
    public const facet_result_xml_context = 'result_xml_';
    public const facet_content_context = 'facet_content_';
    public const result_data_context = 'result_data_';
    public const facet_category_bound_context = 'facet_category_bound_';
    public const facet_category_bound_cache_id = 'facet_category_bound_data';
}

class CacheHelper {

    private static $ttl = 1500;

    public static $disabled = false;

    // Facet Config XML
    public static function get_facet_xml($key)
    {
        return self::Get(CacheKey::facet_xml_context, "{$key}.xml");
    }

    public static function put_facet_xml($key, $data)
    {
        self::Put(CacheKey::facet_xml_context, "{$key}.xml", $data);
    }    

    // Result Config XML
    public static function get_result_xml($key)
    {
        return self::Get(CacheKey::facet_result_xml_context, "{$key}.xml");
    }

    public static function put_result_xml($key, $data)
    {
        return self::Put(CacheKey::facet_result_xml_context, "{$key}.xml", $data);
    }

    public static function get_facet_content($key)
    {
        return self::Get(CacheKey::facet_content_context, $key);
    }

    public static function put_facet_content($key, $data)
    {
        return self::Put(CacheKey::facet_content_context, $key, $data);
    }

    public static function get_result_data($type, $key)
    {
        return self::Get($type . '_' . CacheKey::result_data_context, $key);
    }

    public static function put_result_data($type, $key, $data)
    {
        return self::Put($type . '_' . CacheKey::result_data_context, $key, $data);
    }

    public static function get_range_category_bounds()
    {
        return self::Get(CacheKey::facet_category_bound_context, CacheKey::facet_category_bound_cache_id);
    }

    public static function put_range_category_bounds($data)
    {
        return self::Put(CacheKey::facet_category_bound_context, CacheKey::facet_category_bound_cache_id, $data);
    }

    public static function Get($contextKey, $key)
    {
        return DataCache::Get($contextKey, $key);
    }

    public static function Put($contextKey, $key, $data)
    {
        return DataCache::Put($contextKey, $key, self::$ttl, $data);
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
