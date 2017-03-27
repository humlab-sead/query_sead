<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

/*
file: result_query_compiler.php
This file holds for handling queries and returns content for the client.
see also:
parameters functions - <facet_config.php>
*/

require_once __DIR__ . "/config/environment.php";
require_once __DIR__ . "/config/bootstrap_application.php";
require_once __DIR__ . '/facet_config.php';
require_once __DIR__ . '/facet_content_loader.php';
require_once __DIR__ . '/query_builder.php';

class ResultQueryCompiler {

    private static function prepare_result_params($facetConfig, $resultConfig)
    {
        // prepares params for the query builder.
        // use aggregation level from resultConfig
        // aggregation code.
        global $facet_definition, $result_definition;

        if (empty($resultConfig["items"])) {
            return NULL;
        }

        $f_code = "result_facet";
        $query_column = $facet_definition[$f_code]["id_column"];
        $group_by_str = "";
        $alias_counter = 1;
        $client_language = $facetConfig["client_language"];
        $sep = "";

        // Control which columns and tables should be used in the select clause, depending on what is choosen in the gui.
        foreach ($resultConfig["items"] as $item) {
            // The columns are stringed together., first item is the aggregation_level
            if (empty($item) || $result_definition[$item]["result_item"]) {
                continue;
            }
            foreach ($result_definition[$item]["result_item"] as $res_def_key => $definition_item) {
                foreach ($definition_item as $item_type => $item) {
                    $alias_name = "alias_" . $alias_counter++;
                    $data_fields_alias .= $sep . $item["column"] . "  AS " . $alias_name;
                    $data_tables[] = $item["table"];
                    $group_by_str_inner .= $sep . $alias_name;
                    switch ($res_def_key) {
                        case "sum_item":
                            $data_fields .= $sep . "sum(" . $alias_name . "::double precision) AS sum_of_" . $alias_name;
                            break;
                        case "count_item":
                            $data_fields .= $sep . "count(" . $alias_name . ") AS count_of_" . $alias_name;
                            break;
                        case "avg_item":
                            $data_fields .= $sep . "avg(" . $alias_name . ") AS avg_of_" . $alias_name;
                            break;
                        case "text_agg_item":
                            $data_fields .= $sep . "array_to_string(array_agg(distinct " . $alias_name . "),',') AS text_agg_of_" . $alias_name;
                            break;
                        case "sort_item":
                            $sort_fields .= $sep . $alias_name;
                            $group_by_str .= $sep . $alias_name;
                            break;
                        case "single_item":
                        default:
                            $data_fields .= $sep . $alias_name;
                            $group_by_str .= $sep . $alias_name;
                            break;
                    }
                    $sep = " , ";
                }
            }
        }
        if (!empty($data_tables)) {
            $data_tables = array_unique($data_tables); // Removes multiple instances of same table.
        }
        $return_object["data_fields"] = $data_fields;
        $return_object["group_by_str"] = $group_by_str;
        $return_object["group_by_str_inner"] = $group_by_str_inner;
        $return_object["data_fields_alias"] = $data_fields_alias;
        $return_object["sort_fields"] = $sort_fields;
        $return_object["data_tables"] = $data_tables;
        return $return_object;
    }

    //***************************************************************************************************************************************************
    //
    /*
    function: compileQuery
    Function the generates the sql-query of html-output and data to download
    there are different type of variables which affects the aggregation functinoality in the query.
    It uses the "result_facet as a starting point and adds all the selected variables to be included in the output.
    For aggregated values there is countíng column being defined for each result variable
    see also:
    <get_facet_content>
    <get_joins>
    */
    public static function compileQuery($facetConfig, $resultConfig)
    {
        $return_object = self::prepare_result_params($facetConfig, $resultConfig);

        if (empty($return_object) || empty($return_object["data_fields"])) {
            return "";
        }

        $data_fields = $return_object["data_fields"];
        $group_by_str = $return_object["group_by_str"];
        $group_by_str_inner = $return_object["group_by_str_inner"];
        $data_fields_alias = $return_object["data_fields_alias"];
        $sort_fields = $return_object["sort_fields"];
        $data_tables = $return_object["data_tables"];
        $facetCode = "result_facet";

        $facetCodes = FacetConfig::getKeysOfActiveFacets($facetConfig);
        $facetCodes[] = $facetCode; // Add result_facet as final facet
        
        $query = QueryBuildService::compileQuery($facetConfig, $facetCode, $data_tables, $facetCodes);

        $extra_join      = $query["joins"];
        $tables          = $query["tables"];
        $where_clause    = ($query["where"] != '') ? " and  " . $query["where"] : "";
        $group_by_clause = !empty($group_by_str) ? " group by $group_by_str  " : "";
        $sort_by_clause  = !empty($sort_fields) ? " order by $sort_fields " : "";

        $q =<<<EOS
            select $data_fields
            from (
                select $data_fields_alias
                from $tables
                $extra_join
                where 1 = 1  
                $where_clause
                group by $group_by_str_inner
            ) as tmp 
            $group_by_clause
            $sort_by_clause
EOS;

        return $q;
    }
}




?>
