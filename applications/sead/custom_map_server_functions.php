<?php
/*
file: custom_map_server_functions.php (SEAD)
This file hold server function for the map component
*/

function get_xy_statistics($conn, $lat,$lng, $result_code)
{
    global $result_definition;
    return $info_text;
}

/*
function: get_xy_info
get the info for a coordinate in the result map
returns a simple information-object in HTML.
*/
function get_xy_info($conn,$lat,$lng,$result_code)
{
    return	null;
}

function get_empty_zoom_and_center_coordinates()
{
	$coordinates_xml="	<data_extent><sw_lng>11.12353515625</sw_lng>
                    <sw_lat>55.344554901123</sw_lat>
                    <ne_lng>24.0666389465332</ne_lng>
                    <ne_lat>68.1552658081055</ne_lat>
                    </data_extent>";
    return $coordinates_xml;
}

function build_wms_link($query_id)
{
    $base_link="http://geoserver.humlab.umu.se:8080/geoserver/sead/wfs?version=1.0.0&typeNames=sead:qsead_wms_map_result_publication&srs=EPSG:4326&viewparams:query_id:8&SERVICE=WFS&VERSION=1.0.0&REQUEST=GetFeature&TYPENAME=sead:qsead_wms_map_result_publication&SRSNAME=EPSG:4326";
    return $base_link."&viewparams=query_id:".$query_id;
}

function store_site_ids($conn,&$site_id_list)
{
    $q="insert into metainformation.wms_map_result_publication DEFAULT VALUES returning * ; ";
    $rs = pg_query($conn, $q); 
    $row = pg_fetch_assoc($rs);
    $query_id= $row["query_id"];
    $data_query="insert into metainformation.wms_map_result_publication_sites (query_id,site_id) values ";
    $data_query.= implode ( ",",array_map(function($x) use ($query_id){ return '('. implode (",",array($query_id,$x)).')'; },$site_id_list));
    $rs = pg_query($conn, $data_query);
    return $query_id;
} 
/*
 Function: result_render_map_view (SEAD)
Render diagram  map data for SEAD
*/

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

	if (isset($direct_count_column) &&!empty($direct_count_column)  ) 
	{
        $direct_counts=get_discrete_counts($conn,  $f_code,  $facet_params,$interval, $direct_count_table,$direct_count_column);
        $filtered_direct_counts= $direct_counts["list"];
	}

    $no_selection_params=erase_selections($facet_params);

	if (isset($direct_count_column) &&!empty($direct_count_column)  ) 
	{
	    $direct_counts=get_discrete_counts($conn,  $f_code,  $no_selection_params,$interval, $direct_count_table,$direct_count_column);
		$un_filtered_direct_counts= $direct_counts["list"];
	}

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

	if (($rs = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query map. $q \n"; exit; }

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