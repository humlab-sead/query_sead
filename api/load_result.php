<?php
/*
file: load_result.php

 This file contains functions called by the client when the result area needs to be populated. 

About:
 It handles 3 type of output:
 * list
 * diagram
 * map
 * piechart
Since this framework is made for different domain some of the function are defined differently for different domain.
Domain speicific function are stored in applications/ships and  applications/sead applications/diabas applications/xxx

Trigged by:
Javascript function <result_load_data> in <result.js>

Description:

All domain share the result list, but diagram and map function are different for each application.
It means there also specific functions used on client side. 
However the function always have the same name, but depending in which applicationName it will load different library of code.
Apart from the generic result workspace items there will be different XML-schemas for different result tabs.
Sometimes a result modules can used multiple XML-schemas.

see <fb_server_client_params_funct.php> for function that parses XML document from client.

Facet xml post: 
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/facet_post_xml.html
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/facet_post.xsd

Result xml post:
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/result_xml_post-schema.htm
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/result_xml_post-schema.xsd


XML response for the list tab  (shared across all applications): 
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/result_response_list.xsd
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/result_response_list.html


XML post for result map:
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/result_map_post_schema.htm
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/result_map_post_schema.xsd

XML result map response for thematic mapping in map tab:
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/result_map_response_raster_overlay_schema.xsd
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/result_map_response_raster_overlay_schema.htm

Shared sequence for list, diagram, and map:
	* initiate database connection using definition in fb_def.php see  <fb_def.php (SHIPS)> and <fb_def.php (SEAD)>
	* Process facet_xml post using <FacetConfigDeserializer::deserializeFacetConfig> and store the post into a composite array
	* Remove invalid discrete selection with the function <FacetConfig::removeInvalidUserSelections>
	* Process result_xml post document using <ResultConfigDeserializer::deserializeResultConfig> storing the post into a composite array/objects

Sequence for list-data operation:
	* Make cache-data identifier using function derive_selection 
	* Render list using  <result_render_list_view_html>
	* Save the query into a table (SEAD only?)

Sequence for diagram load operation:
	* Process the postdocument for the diagram options using <ResultConfigDeserializer::deserializeDiagramConfig>, stores the result in composite array
	* Add all result_items from the result_xml document as potential y-axis in the diagram
	* Derive the combinations of result variables and aggregations units e.g. one result variable for all parishes/units or one parish/unit and all result variables using the function get_group_y_data in custum_server_functions.php
	* Handle exception if the client has not sent a valid/undefined y-variable or aggregation unit (such as county or a parish)
	* Render the diagram data to be used using <result_render_diagram_data> in custom_server_functions.php. This will return the xml-data to be sent back to the client.

Sequence for map load operation:
	* Process xml-document for map-parameters using function using <ResultConfigDeserializer::deserializeMapConfig>. It stores the parameters into a composite array.
	* Process symbol-xml document (if present) and make array for point symbols (SEAD/DIABAS)
	* Render the map output for the client, which is different for each application (SEAD/SHIPS/DIABAS etc)
	* Functions on client-side are also different to handle the different kind of map-output.

	<custom_map_server_functions.php>
*/

require_once __DIR__ . '/../server/fb_server_funct.php';
require_once __DIR__ . '/../server/custom_map_server_functions.php';
require_once __DIR__ . '/../server/lib/Cache.php';
require_once __DIR__ . '/../server/connection_helper.php';

global $result_definition_item, $application_name, $cache_seq; 

$conn = ConnectionHelper::createConnection();

// The xml-data is containing facet information is processed and all parameters are put into an array for futher use.
//$facet_xml = $_REQUEST['facet_xml'];

$facet_state_id = $_REQUEST['facet_state_id'];

if (!empty($facet_state_id))
{
    $facet_xml = get_facet_xml_from_id($facet_state_id);
} else {
    $facet_xml = $_REQUEST['facet_xml'];
}

$facetConfig = FacetConfigDeserializer::deserializeFacetConfig($facet_xml); 
$facetConfig = FacetConfig::removeInvalidUserSelections($conn, $facetConfig);

// The xml-data contains result information, and is processed and all parameters are put into an array for futher use.

$result_xml = $_REQUEST['result_xml'];
$diagram_xml = $_REQUEST['diagram_xml'];
$map_xml = $_REQUEST['map_xml'];
$symbol_xml = $_REQUEST['symbol_xml'];

$resultConfig = ResultConfigDeserializer::deserializeResultConfig($result_xml);


    switch($resultConfig["view_type"]) {

        case "map":
            $map_params = ResultConfigDeserializer::deserializeMapConfig($map_xml);
            if (!empty($symbol_xml)) {
                $symbol_params = ResultConfigDeserializer::deserializeMapSymbolConfig($symbol_xml);
            }
            $aggregation_code=$resultConfig["aggregation_code"];
            $out = result_render_map_view($conn,$facetConfig,$resultConfig,$map_params,$facet_xml,$result_xml,$map_xml,$aggregation_code);
            $out = "<aggregation_code>".$resultConfig["aggregation_code"]."</aggregation_code>\n<result_html>$result_list</result_html>" . $out ;
            break;

        case "diagram":
            // get the y_axis from the resultConfig
            if (!empty($diagram_xml))
            {
                $diagram_params = ResultConfigDeserializer::deserializeDiagramConfig($diagram_xml);
                $x_axis = $diagram_params["diagram_x_code"];
                $group_client_id = $diagram_params["group_id"];
            }
            foreach($resultConfig["items"] as $item)
            {
                // First create header for the column.
                foreach ($result_definition[$item]["result_item"] as $res_def_key => $definition_item)
                {
                    if ($res_def_key=='sum_item' || ($resultConfig["aggregation_code"]=='parish_level' && $res_def_key=='single_sum_item'))
                        $y_axis[]=$item;
                }
            }

            $aggregation_code = $resultConfig["aggregation_code"]; // parish_level, county_level, year_level

            if (count($resultConfig["items"])<=1) {
            // make a empty diagram if thera are none resultvariable (apart from the aggregation_code)
                $out=result_render_empty_diagram($aggregation_code);
            }
            else
            { // make diagram data

                $return_obj=get_group_y_data($conn,$y_axis,$facetConfig, $resultConfig,$aggregation_code);
                $y_items=$return_obj["y_items"];
                $count_of_series=$return_obj["count_of_series"];
                // check what type of group is selected as Y-values (stat_unit or aggregation_unit)
                // assigne the first group if the group is unknown or undefined
                // Otherwise it will be the value from the client post
                // also check that the client_id is corrensponding to a value in the list of groups
                // || empty($group_extra[$group_client_id]
                if ($group_client_id=="undefined" ) 
                    {
                        $g_counter=0;
                        if (!empty($y_items)) {
                            foreach ($y_items as $key =>$group_element) {
                                if ($g_counter==0)
                                    $group_client_id=$y_items[$key]["group_id"];		// set the group_client_id to the first item by default  (will be overwritten if possible)		
                                $g_counter++;
                            }
                        }
                    }  else {

                        $cmp_client_type = substr($group_client_id,0,9); // check for stat_unit, else it will be aggregation_unit

                        $group_type = $cmp_client_type == "stat_unit" ? "stat_unit" : "aggregation_unit";

                        if (empty($y_items[$group_client_id] )) {
                            $g_counter=0;
                            if (!empty($y_items)) {
                                foreach ($y_items as $key => $group_element) {
                                        if ($g_counter==0 && ($group_type==$y_items[$key]["group_type"])) {
                                            $group_client_id=$y_items[$key]["group_id"];				
                                            $g_counter++;
                                        }
                                    }
                                }
                            }
                        else 
                            $group_client_id=$group_client_id;
                    }
                $max_count_series=25;
                $d_str=(string) $resultConfig["view_type"]."_".FacetConfig::generateUserSelectItemsCacheId($facetConfig).derive_result_selection_str($result_xml).$aggregation_code.$group_client_id.$x_axis.$facetConfig["client_language"];
                if ( !$out = DataCache::Get("diagram_data_".$applicationName, $d_str)) { 
                    $out = result_render_diagram_data($conn,$facetConfig, $resultConfig,$x_axis,$y_axis,$y_items,$group_client_id,$aggregation_code,$max_count_series,$count_of_series);
                    DataCache::Put("diagram_data_".$applicationName, $d_str, 1500,$out);    
                }
            }

            $current_request_id=$diagram_params['request_id'];
            $out="<aggregation_code>".$resultConfig["aggregation_code"]."</aggregation_code>".$out;

            break;

        case "list":

            if (!isset($conn)) {
                break;
            }
            $f_str=(string) $resultConfig["view_type"]."_".FacetConfig::generateUserSelectItemsCacheId($facetConfig).derive_result_selection_str($result_xml).$facetConfig["client_language"].$resultConfig["aggregation_code"];
            
            if (!isset($facet_state_id))
            {
                $cache_seq_id = $cache_seq ?? 'file_name_data_download_seq';
                $row = ConnectionHelper::queryRow($conn, "select nextval('$cache_seq_id') as cache_id;");
                $facet_state_id = $application_name . $row["cache_id"];
                file_put_contents(__DIR__."/cache/".$facet_state_id."_facet_xml.xml",$facet_xml);
            }
            file_put_contents(__DIR__."/cache/".$facet_state_id."_result_xml.xml", $result_xml);

            $data_link="/api/report/get_data_table.php?cache_id=".$facet_state_id."&application_name=$applicationName";
            $data_link_text="/api/report/get_data_table_text.php?cache_id=".$facet_state_id."&application_name=$applicationName";

            // new data link with a file_name that is unique point to facet_xml_file in the cache_catalogue
            switch ($resultConfig["client_render"])
            {
                case "xml":
                    $out=result_render_list_view_xml($conn,$facetConfig,$resultConfig,$data_link,$facet_state_id,$data_link_text);
                    break;
                default: 
                    if (!$out = DataCache::Get("result_list".$applicationName, $f_str)) { 
                        $out = result_render_list_view_html($conn,$facetConfig,$resultConfig,$data_link,$facet_state_id,$data_link_text);
                        DataCache::Put("result_list".$applicationName, $f_str, 1500,$out);    
                    }
                    $out = "<![CDATA[".$out."]]>";   
                    break;
            }
            // $facet_string_params=$resultConfig["session_id"]."_".FacetConfig::generateUserSelectItemsCacheId($facetConfig);
            // if (!$save_set_sql = DataCache::Get("save_sets_sql".$applicationName, $facet_string_params)) { 
            //     $save_set_sql=get_save_query($facetConfig,$resultConfig["session_id"], $conn);
            //     DataCache::Put("save_sets_sql".$applicationName, $facet_string_params, 1500,$save_set_sql);    
            // }
            $current_request_id=$resultConfig['request_id'];

        break;
    }

header("Content-Type: text/xml");
header("Character-Encoding: UTF-8");
$meta_data_str=FacetConfig::generateUserSelectItemHTML($facetConfig);
// request id sent back to clietn
$current_request_id=$resultConfig["request_id"];

$meta_xml = "<current_selections><![CDATA[".$meta_data_str."]]></current_selections>";;
$xml = "<xml><response>".$out."</response>".$meta_xml."<request_id>" . $current_request_id . "</request_id></xml>";

echo $xml;
pg_close($conn);

?>