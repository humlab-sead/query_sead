<?php
/*
file: load_result.php

 This file contains functions called by the client when the result area needs to be populated. 

About:
 It handles 3 type of output:
 * list
 * REMOVED diagram
 * map
 * REMOVED piechart
NO LONGER VALID: Since this framework is made for different domain some of the function are defined differently for different domain.
NO LONGER VALID: Domain speicific function are stored in applications/ships and  applications/sead applications/diabas applications/xxx

Trigged by:
Javascript function <result_load_data> in <result.js>

Description:

All domain share the result list, but diagram and map function are different for each application.
It means there also specific functions used on client side. 
However the function always have the same name, but depending in which applicationName it will load different library of code.
Apart from the generic result workspace items there will be different XML-schemas for different result tabs.
Sometimes a result modules can used multiple XML-schemas.

see <facet_config.php> for function that parses XML document from client.

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
	* initiate database connection using definition in bootstrap_application.php
	* Process facetXml post using <FacetConfigDeserializer::deserializeFacetConfig> and store the post into a composite array
	* Remove invalid discrete selection with the function <FacetConfig::removeInvalidUserSelections>
	* Process result_xml post document using <ResultConfigDeserializer::deserializeResultConfig> storing the post into a composite array/objects

Sequence for list-data operation:
	* Make cache-data identifier using function derive_selection 
	* Render list using  <render_html>
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

	<result_render_map_view.php>
*/

require_once __DIR__ . '/../server/connection_helper.php';
require_once __DIR__ . '/../server/lib/Cache.php';
require_once __DIR__ . '/../server/facet_config.php';
require_once __DIR__ . '/../server/cache_helper.php';
require_once __DIR__ . '/../server/compile_result.php';

global $result_definition_item, $application_name, $cache_seq; 

$conn = ConnectionHelper::createConnection();

$facetStateId = $_REQUEST['facet_state_id'];

$facetXml    = !empty($facetStateId) ? CacheHelper::get_facet_xml_from_id($facetStateId) : $_REQUEST['facet_xml'];

$facetConfig = FacetConfigDeserializer::deserializeFacetConfig($facetXml); 
$facetConfig = FacetConfig::removeInvalidUserSelections($conn, $facetConfig);

$result_xml  = $_REQUEST['result_xml'];
$map_xml     = $_REQUEST['map_xml'];
$symbol_xml  = $_REQUEST['symbol_xml'];

$resultConfig = ResultConfigDeserializer::deserializeResultConfig($result_xml);

switch($resultConfig["view_type"]) {

    case "map":
        $map_params = ResultConfigDeserializer::deserializeMapConfig($map_xml);
        if (!empty($symbol_xml)) {
            $symbol_params = ResultConfigDeserializer::deserializeMapSymbolConfig($symbol_xml);
        }
        $aggregation_code=$resultConfig["aggregation_code"];
        $out = result_render_map_view($conn,$facetConfig,$resultConfig,$map_params,$facetXml,$result_xml,$map_xml,$aggregation_code);
        $out = "<aggregation_code>".$resultConfig["aggregation_code"]."</aggregation_code>\n<result_html>$result_list</result_html>" . $out ;
        break;
    case "list":
        $f_str = CacheHelper::computeResultConfigCacheId($facetConfig, $resultConfig, $result_xml);
        if (!isset($facetStateId))
        {
            $cache_seq_id = $cache_seq ?? 'file_name_data_download_seq';
            $row = ConnectionHelper::queryRow($conn, "select nextval('$cache_seq_id') as cache_id;");
            $facetStateId = $application_name . $row["cache_id"];
            file_put_contents(__DIR__."/cache/".$facetStateId."_facet_xml.xml",$facetXml);
        }
        file_put_contents(__DIR__."/cache/".$facetStateId."_result_xml.xml", $result_xml);

        $data_link="/api/report/get_data_table.php?cache_id=".$facetStateId."&application_name=$applicationName";
        $data_link_text="/api/report/get_data_table_text.php?cache_id=".$facetStateId."&application_name=$applicationName";

        // new data link with a file_name that is unique point to facet_xml_file in the cache_catalogue
        switch ($resultConfig["client_render"])
        {
            case "xml":
                $out=RenderResultListXML::render_xml($conn,$facetConfig,$resultConfig,$data_link,$facetStateId,$data_link_text);
                break;
            default: 
                if (!$out = DataCache::Get("result_list".$applicationName, $f_str)) { 
                    $out = RenderResultListHTML::render_html($conn,$facetConfig,$resultConfig,$data_link,$facetStateId,$data_link_text);
                    DataCache::Put("result_list".$applicationName, $f_str, 1500,$out);    
                }
                $out = "<![CDATA[".$out."]]>";   
                break;
        }
        break;
}

header("Content-Type: text/xml");
header("Character-Encoding: UTF-8");
$meta_data_str=FacetConfig::generateUserSelectItemHTML($facetConfig);
$current_request_id=$resultConfig["request_id"];

$meta_xml = "<current_selections><![CDATA[".$meta_data_str."]]></current_selections>";;
$xml = "<xml><response>".$out."</response>".$meta_xml."<request_id>" . $current_request_id . "</request_id></xml>";

echo $xml;
pg_close($conn);

?>