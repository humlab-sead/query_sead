<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(__DIR__ . "/config/environment.php");
require_once(__DIR__ . "/config/bootstrap_application.php");
include_once(__DIR__ . "/lib/Cache.php");

class ConfigurationRegistry 
{
    public static function GetFacetDefinitions()
    {
        global $facet_definition;
        return $facet_definition;
    }
}

class CacheService
{
    public static function Get($key1, $key2)
    {
        global $applicationName;
        return DataCache::Get($key1.$applicationName, $key2);
    }

    public static function Put($key1, $key2, $timeout, $data)
    {
        global $applicationName;
        DataCache::Put($key1.$applicationName, $key2, $timeout, $data);
    }
}

?>
