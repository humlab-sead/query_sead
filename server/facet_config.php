<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once __DIR__ . '/query_builder.php';
require_once __DIR__ . '/lib/utility.php';

/*
file: facet_config.php
This file holds all functions to process and handle params and xml-data from client
*/

class FacetConfig
{

    //***************************************************************************************************************************************************
    /*
    function:  FacetConfig::deleteBogusPicks
    Removes invalid selections e.g. hidden selections still being sent from the client.
    The client keep them since they can be visible when the filters changes
    This is only applicable for discrete facets (range facet selection are always visible)

    class FacetConfig
    */
    public static function deleteBogusPicks($conn, $facetConfig)
    {
        $codesWithPicks = self::getCodesOfActiveFacetsWithPicks($facetConfig);
        $pickGroups = self::getUserPickGroups($facetConfig);
        foreach ($codesWithPicks ?: [] as $key => $facetCode) {
            $facet = FacetRegistry::getDefinition($facetCode);        
            if ($facet["facet_type"] != "discrete") {
                continue;
            }
            $query = QueryBuildService::compileQuery2($facetConfig, $facetCode);

            $picks = self::getDiscreteUserPicks($pickGroups[$facetCode]);
            $picks_clause = array_join_surround($picks, ",", "('", "'::text)", "");
            $query_where = str_prefix("AND ", $query["where"]);
            $sql = "

                SELECT DISTINCT pick_id, {$facet["name_column"]} AS name_item
                FROM {$query["tables"]}
                JOIN (
                    VALUES {$picks_clause}
                ) AS x(pick_id)
                    ON x.pick_id = {$facet["id_column"]}::text
                    {$query["joins"]}
                WHERE 1 = 1
                    $query_where

            ";

            $values = [];
            $rows = ConnectionHelper::queryRows($conn, $sql);
            foreach ($rows as $row) {
                $values[] = [
                    "selection_type" => "discrete",
                    "selection_value" => $row["pick_id"],
                    "selection_text" => $row["name_item"]
                ];
            }
            $facetConfig["facet_collection"][$facetCode]["selection_groups"]["selection_group"]["selection"] = $values;
        }
        return $facetConfig;
    }

    /*
    function: FacetConfig::getCodesOfActiveFacets
    get the list of facets in the user interface currently.
    derive the order of the facet and returns a list in order the facets are arranged.
    */

    public static function getCodesOfActiveFacets($facetConfig)
    {
        if (empty($facetConfig["facet_collection"])) {
            return null;
        }
        $usedFacets = array_filter($facetConfig["facet_collection"], function ($item) {
            return isset($item["facet_position"]);
        });
        foreach ($usedFacets as $facetCode => $item) {
            $used_facet_index[$item["facet_position"]] = $facetCode;
        }
        ksort($used_facet_index);
        return $used_facet_index;
    }

    /*
    function getUserPickGroups
    Extracts user selections from a facetConfig as SimpleXML object!!!
    */
    public static function getUserPickGroups($facetConfig)
    {
        if (empty($facetConfig["facet_collection"])) {
            return null;
        }
        $facetsWithSelection = array_filter($facetConfig["facet_collection"], function ($item) {
            return !empty($item["selection_groups"]);
        });
        return array_map(function ($item) {
            return $item["selection_groups"];
        }, $facetsWithSelection);
    }

    //***************************************************************************************************************************************************
    /*
    function:  FacetConfig::getDiscreteValuesSelectedByUser
    get the selection value from a selection group from the facet_xml-data array
    */

    private static function getDiscreteUserPicks($xmlArray)
    {
        if (!isset($xmlArray)) {
            return null;
        }
        $items = [];
        foreach ($xmlArray as $x => $xmlGroups) {
            foreach ($xmlGroups as $y => $xmlGroup) {
                foreach ($xmlGroup as $selections) {
                    $items[] = (string)((array)$selections)["selection_value"];
                }
            }
        }
        return $items;
    }

    /*
    function: FacetConfig:::getFacetKeysWithUserSelections
    this function derives the selection of the facetConfig
    */

    private static function getCodesOfActiveFacetsWithPicks($facetConfig)
    {
        $activeCodes = FacetConfig::getCodesOfActiveFacets($facetConfig);
        $groups = FacetConfig::getUserPickGroups($facetConfig);
        if (!empty($activeCodes) && !empty($groups)) {
            return array_filter($activeCodes, function ($x) use ($groups) {
                return array_key_exists($x, $groups);
            });
        }
        return [];
    }

    /*
    function deleteUserPicks
    this function deleteUserPicks the selections from facetConfig
    */
    public static function deleteUserPicks($facetConfig)
    {
        if (empty($facetConfig["facet_collection"])) {
            return $facetConfig;
        }
        foreach ($facetConfig["facet_collection"] as $facetCode => $facet) {
            if (!empty($facet["selection_groups"])) {
                $facetConfig["facet_collection"][$facetCode]["selection_groups"] = "";
            }
        }
        return $facetConfig;
    }

    /*
    function computeUserSelectItemCount
    */
    /*
    Function: FacetConfig::collectUserPicks
    Collects user picks/selections from active facets.
    */

    public static function collectUserPicks($facetConfig, $currentCode = false)
    {
        $activeCodes = FacetConfig::getCodesOfActiveFacets($facetConfig);
        $facetPicks = FacetConfig::getUserPickGroups($facetConfig);
        
        // goes through the facet list and stores the selection of each (type of) facet
        if (empty($activeCodes)) {
            return null;
        }
        $matrix = [ 'counts' => [] ];
        foreach ($activeCodes as $pos => $facetCode) {
            $applicable = isset($facetPicks[$facetCode]) && ($currentCode == $facetCode || $currentCode === false);
            if (!$applicable) {
                continue;
            }
            $facet = FacetRegistry::getDefinition($facetCode);
            $is_range = $facet["facet_type"] == "range";
            $matrix[$facetCode]["display_title"] = $facet["display_title"];
            foreach ($facetPicks[$facetCode] as $group) {
                foreach ($group as $item) {
                    $picks = [];
                    foreach ($item as $value) {
                        $value = (array)$value;
                        $picks[] = $is_range ? $value["selection_value"] : $value;
                        $matrix['count'][$facet["facet_type"]] += count($value);
                    }
                    $matrix[$facetCode]["selections"] = $is_range ? [ implode(" - ", $picks) ] : $picks;
                }
            }
        }
        return $matrix;
    }
}
