<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once("../applications/applicationSpecification.php");
require_once("../applications/sead/fb_def.php");
include_once ("../server/lib/Cache.php");

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
