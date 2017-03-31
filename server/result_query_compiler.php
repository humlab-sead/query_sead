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

    // private static function getResultConfigItems($resultConfig)
    // {
    //     global $result_definition;
    //     $items = [];
    //     foreach ($resultConfig["items"] as $aggregate_level) {
    //         if (empty($aggregate_level)) {
    //             continue;
    //         }
    //         foreach ($result_definition[$aggregate_level]["result_item"] as $res_def_key => $definition_item) {
    //             $items[$res_def_key] = $definition_item;
    //         }
    //     }
    //     return $items;
    // }

    private static function prepare_result_params($facetConfig, $resultConfig)
    {
        // prepares params for the query builder - use aggregation level from resultConfig  aggregation code.
        global $result_definition;

        if (empty($resultConfig["items"])) {
            return NULL;
        }

        $facetCode = "result_facet";

        $group_by_fields = [];
        $group_by_inner_fields = [];
        $data_fields = [];
        $sort_fields = [];
        $alias_counter = 1;

        foreach ($resultConfig["items"] as $aggregate_level) {
            if (empty($aggregate_level)) {
                continue;
            }
            foreach ($result_definition[$aggregate_level]["result_item"] as $res_def_key => $definition_item) {

                foreach ($definition_item as $item_type => $item) {
                    $alias_name = "alias_" . $alias_counter++;
                    $data_fields_alias[] = "{$item['column']} As {$alias_name}";
                    $data_tables[] = $item["table"];
                    $group_by_inner_fields[] = "{$alias_name}";
                    switch ($res_def_key) {
                        case "sum_item":
                            $data_fields[] = "sum({$alias_name}::double precision) As sum_of_{$alias_name}";
                            break;
                        case "count_item":
                            $data_fields[] = "count({$alias_name}) As count_of_{$alias_name}";
                            break;
                        case "avg_item":
                            $data_fields[] = "avg({$alias_name}) As avg_of_{$alias_name}";
                            break;
                        case "text_agg_item":
                            $data_fields[] = "array_to_string(array_agg(distinct {$alias_name}),',') As text_agg_of_{$alias_name}";
                            break;
                        case "sort_item":
                            $sort_fields[] = $alias_name;
                            $group_by_fields[] = $alias_name;
                            break;
                        case "single_item":
                        default:
                            $data_fields[] = $alias_name;
                            $group_by_fields[] = $alias_name;
                            break;
                    }
                }
            }
        }
        if (!empty($data_tables)) {
            $data_tables = array_unique($data_tables); // Removes multiple instances of same table.
        }
        $return_object["data_fields"] = implode(", ", $data_fields);
        $return_object["group_by_str"] = implode(", ", $group_by_fields);
        $return_object["group_by_str_inner"] = implode(", ", $group_by_inner_fields);
        $return_object["data_fields_alias"] = implode(", ", $data_fields_alias);
        $return_object["sort_fields"] = implode(", ", $sort_fields);
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

function result_render_map_view($conn,$facet_params,$result_params,$facet_xml,$result_xml)
{
	global $facet_definition,$direct_count_table,$direct_count_column ;

	$f_code="map_result";
	$query_column = $facet_definition[$f_code]["id_column"];
	$name_column = $facet_definition[$f_code]["name_column"];
	$lat_column="latitude_dd";
	$long_column="longitude_dd";

	$tmp_list=derive_facet_list($facet_params);
	$tmp_list[]=$f_code; 
	$interval=1;

	// if (isset($direct_count_column) &&!empty($direct_count_column)  ) 
	// {
    //     $direct_counts=get_counts($conn,  $f_code,  $facet_params,$interval, $direct_count_table,$direct_count_column);
    //     $filtered_direct_counts= $direct_counts["list"];
	// }
    // $no_selection_params=erase_selections($facet_params);
	// if (isset($direct_count_column) &&!empty($direct_count_column)  ) 
	// {
	//     $direct_counts=get_counts($conn,  $f_code,  $no_selection_params,$interval, $direct_count_table,$direct_count_column);
	// 	$un_filtered_direct_counts= $direct_counts["list"];
	// }

	$query = get_query_clauses( $facet_params, $f_code, $data_tables,$tmp_list);

	$extra_join=$query["joins"];
	$table_str=$query["tables"];

	if ($extra_join!="")
		$and_command=" and ";

	$q.="select  distinct $name_column as name , $lat_column,$long_column,  " . $query_column." as id_column from ".$table_str."   $extra_join where 1=1   ";

	if ($query["where"]!='') 
	{
		$q.=" and  ".$query["where"];	
	} 
	return $out;
}

class MapResultQueryCompiler {

    public static function compileQuery($facetConfig, $facetCode)
    {
        global $facet_definition;

        $facetCode="map_result"; // override argument
        $query_column = $facet_definition[$facetCode]["id_column"];
        $name_column = $facet_definition[$facetCode]["name_column"];
        $lat_column = "latitude_dd";
        $long_column = "longitude_dd";

        $resultFacetCodes = FacetConfig::getKeysOfActiveFacets($facetConfig);
        $resultFacetCodes[] = $facetCode; 
        
        $query = QueryBuildService::compileQuery($facetConfig, $facetCode, $data_tables, $resultFacetCodes);
        $extra_join = $query["joins"];
        $query_tables = $query["tables"];
        $filter_clauses = $query["where"] != '' ? " and " . $query["where"] : "";	
        $q = "select distinct $name_column as name, $lat_column, $long_column, $query_column as id_column " .
             "from $query_tables $extra_join " .
             "where 1 = 1 " .
             "  $filter_clauses ";
        return $q;
    }
}




?>
