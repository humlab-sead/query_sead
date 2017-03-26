<?php
/*
file: custom_map_server_functions.php (SEAD)
This file hold server function for the map component
*/

require_once __DIR__ . '/connection_helper.php';

/*
 Function: result_render_map_view (SEAD)
Render diagram  map data for SEAD
*/

function result_render_map_view($conn,$facetConfig,$resultConfig,$facet_xml,$result_xml)
{
	global $facet_definition,$direct_count_table,$direct_count_column ;
	$f_code="map_result";

	$query_column = $facet_definition[$f_code]["id_column"];
	$name_column = $facet_definition[$f_code]["name_column"];
	
	$lat_column="latitude_dd";
	$long_column="longitude_dd";
	$tmp_list=FacetConfig::getKeysOfActiveFacets($facetConfig);
	$tmp_list[]=$f_code; 
	$interval=1;

	if (isset($direct_count_column) && !empty($direct_count_column)  ) 
	{
        $direct_counts = DiscreteFacetCounter::get_discrete_counts($conn,  $f_code,  $facetConfig,$interval, $direct_count_table,$direct_count_column);
        $filtered_direct_counts = $direct_counts["list"];
	}

    $no_selection_params = FacetConfig::eraseUserSelectItems($facetConfig);

	if (isset($direct_count_column) && !empty($direct_count_column)  ) 
	{
	    $direct_counts = DiscreteFacetCounter::get_discrete_counts($conn,  $f_code,  $no_selection_params,$interval, $direct_count_table,$direct_count_column);
		$un_filtered_direct_counts = $direct_counts["list"];
	}

	$query = get_query_clauses($facetConfig, $f_code, $data_tables, $tmp_list);
	$extra_join = $query["joins"];
	$query_tables = $query["tables"];
	$query_where = $query["where"] != '' ? " and " . $query["where"] : "";	

	$q.="select distinct $name_column as name, $lat_column, $long_column, $query_column as id_column " .
        "from $query_tables $extra_join " .
        "where 1 = 1 $query_where ";

	$rs = ConnectionHelper::query($conn, $q);

	$out.="<sql_info>";
	$out.="<![CDATA[".$q."]]>";
	$out.="</sql_info>";
	$out.="<points>";
	while (($row = pg_fetch_assoc($rs) )) 
	{
		$site_id_list[]=$row["id_column"];	
		$out.="<point>";
		$out.="<name><![CDATA[".$row["name"]."]]></name>";
		$out.="<id>".$row["id_column"]."</id>";
		$out.="<latitude><![CDATA[".$row[$lat_column]."]]></latitude>";
		$out.="<longitude><![CDATA[".$row[$long_column]."]]></longitude>";
		$out.="<filtered_count><![CDATA[".   $filtered_direct_counts[$row["id_column"]]  ."]]></filtered_count>";
		$out.="<un_filtered_count><![CDATA[".   $un_filtered_direct_counts[$row["id_column"]]  ."]]></un_filtered_count>";
		$out.="</point>";
	}
	$out.="</points>";
	return $out;
}
?>