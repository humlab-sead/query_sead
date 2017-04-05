<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

require_once __DIR__ . '/query_builder.php';

/*
file: facet_config.php
This file holds all functions to process and handle params and xml-data from client
*/

class FacetConfig
{

    //***************************************************************************************************************************************************
    /*
    function:  FacetConfig::removeInvalidUserSelections
    Removes invalid selections e.g. hidden selections still being sent from the client.
    The client keep then since they can be visible when the filters changes
    This is only applicable for discrete facets (range facet selection are always visible)

    class FacetConfig
    */

    public static function removeInvalidUserSelections($conn, $facetConfig)
    {
        $keys_with_selections = self::getFacetKeysWithUserSelections($facetConfig);
        $itemsSelectedByUser = self::getItemGroupsSelectedByUser($facetConfig);
        if (empty($keys_with_selections)) {
            return $facetConfig;
        }

        foreach ($keys_with_selections as $key => $facetCode) {
            $facet = FacetRegistry::getDefinition($facetCode);        
            if ($facet["facet_type"] != "discrete") {
                continue;
            }
            $activeKeys = self::getCodesOfActiveFacets($facetConfig);
            
            $query = QueryBuildService::compileQuery($facetConfig, $facetCode, $data_tables, $activeKeys);
            
            $query_column = $facet["id_column"];
            $query_column_name = $facet["name_column"];
            $current_selection = $itemsSelectedByUser[$facetCode];
            $current_selection_values = self::getDiscreteValuesSelectedByUser($current_selection);
            $union_clause = implode(" union ", array_map(function ($x) {
                return  "select '$x'::text as selected_value ";
            }, $current_selection_values));
            $tables = $query["tables"];
            $query_joins = $query["joins"];
            $criteria_clause = (trim($query["where"]) != '') ? " and \n " . $query["where"] : " ";

            $q1 =<<<EOS
            SELECT DISTINCT selected_value, $query_column_name AS name_item
            FROM (
                $union_clause
            ) AS x,
            $tables $query_joins
            WHERE x.selected_value = $query_column::text
                $criteria_clause
EOS;
            // Replace existing data with result of query
            $group = array();
            $rs2 = ConnectionHelper::query($conn, $q1);
            while ($row = pg_fetch_assoc($rs2)) {
                $group["selection2"][] = [ "selection_type" => "discrete", "selection_value" => $row["selected_value"], "selection_text" => $row["name_item"] ];
            }
            $facetConfig["facet_collection"][$facetCode]["selection_groups"]["selection_group"] = $group;
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
    function getItemGroupsSelectedByUser
    Extracts user selections from a facetConfig
    */
    public static function getItemGroupsSelectedByUser($facetConfig)
    {
        if (empty($facetConfig["facet_collection"])) {
            return null;
        }
        $facetsWithSelection = array_filter($facetConfig["facet_collection"], function ($item) {
            return !empty($item["selection_groups"]);
        });
        $itemGroups = array_map(function ($item) {
            return $item["selection_groups"];
        }, $facetsWithSelection);
        return $itemGroups;
    }

    //***************************************************************************************************************************************************
    /*
    function:  FacetConfig::getDiscreteValuesSelectedByUser
    get the selection value from a selection group from the facet_xml-data array
    NOTE! Selected item are not deserialize - they are stored in an XML-object
    */

    private static function getDiscreteValuesSelectedByUser($xmlArray)
    {
        if (!isset($xmlArray)) {
            return null;
        }
        foreach ($xmlArray as $x => $xmlGroups) {
            foreach ($xmlGroups as $y => $xmlGroup) {
                //$items = array_map(function($x) { return ((array)$x)["selection_value"]; }, (array)$xmlGroup);
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

    private static function getFacetKeysWithUserSelections($facetConfig)
    {
        $activeKeys = FacetConfig::getCodesOfActiveFacets($facetConfig);
        $itemsSelectedByUser = FacetConfig::getItemGroupsSelectedByUser($facetConfig);
        if (!empty($activeKeys) && !empty($itemsSelectedByUser)) {
            return array_filter($activeKeys, function ($x) use ($itemsSelectedByUser) {
                return array_key_exists($x, $itemsSelectedByUser);
            });
        }
        return [];
    }

    /*
    function eraseUserSelectItems
    this function eraseUserSelectItems the selections from facetConfig
    */
    public static function eraseUserSelectItems($facetConfig)
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
        $activeKeys = FacetConfig::getCodesOfActiveFacets($facetConfig);
        $facetPicks = FacetConfig::getItemGroupsSelectedByUser($facetConfig);
        
        // goes through the facet list and stores the selection of each (type of) facet
        if (empty($activeKeys)) {
            return null;
        }
        $matrix['counts'] = [];
        foreach ($activeKeys as $pos => $facetCode) {
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
