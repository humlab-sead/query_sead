<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

/*
file: fb_load.php
About:
This file populating the facets with content

There are three types of facets
* Dicrete facet
* Range facet
* REMOVED: Geo facet

It is being called from javascript <facet.js> with the function <facet_load_data>
It returns a xml-document with the content.

XML post:
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/facet_post_xml.html
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/facet_post.xsd

XML response:
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/facet_response_xml.html
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/facet_response.xsd

Shared sequence:
* Process facetConfig using <FacetConfigDeserializer::deserializeFacetConfig> and build compoosite array of the facet_xml-document being posted from the client
* Remove invalid selections using <FacetConfig::removeInvalidUserSelections> since selections are keep at the client altough the might be filter out.
* Derive a compsosite ID for caching of the facet content.
* Render the data for the facet using function <FacetContentService::load>
* Computing start_row and limit if the text-search is being used.
* Output the parts of the facet's data to the client using <FacetContentSerializer::serializeFacetContent>, depending on how much data the client requests or defined by text-search start_row
*/

require_once __DIR__ . '/../server/connection_helper.php';
require_once(__DIR__ . "/../server/lib/utility.php");
require_once(__DIR__ . "/../server/facet_content_loader.php");
require_once(__DIR__ . "/serializers/facet_config_deserializer.php");
require_once(__DIR__ . "/serializers/facet_content_serializer.php");

$xml = (!empty($_REQUEST["xml"])) ? $_REQUEST["xml"] : NULL;

$facetConfig = FacetConfigDeserializer::deserializeFacetConfig($xml);

$conn = ConnectionHelper::createConnection();

$facetCode     = $facetConfig["requested_facet"];
$facet_options = $facetConfig["facet_collection"][$facetCode];
$action_type   = $facetConfig["f_action"][1];
$facet         = FacetRegistry::getDefinition($facetCode);
$facetType     = $facet["facet_type"];
$query_offset  = $facet_options["facet_start_row"];
$query_limit   = $facet_options["facet_number_of_rows"];

$facetConfig   = FacetConfig::removeInvalidUserSelections($conn, $facetConfig);
$facetContent  = FacetContentService::load($conn, $facetConfig);

if ($action_type=="populate_text_search") {
    $textFilter = $facet_options["facet_text_search"];
    $facet_rows = $facetContent[$facetCode]["rows"];
    $query_offset = ArrayHelper::findIndex($facet_rows, $textFilter);
    $query_offset = max(0, min($query_offset, $facetContent[$facetCode]['total_number_of_rows'] - 12));
}

if ($facetType == "range") {
    $query_offset = 0;
    $query_limit = 250;
}
pg_close($conn);

$response = FacetContentSerializer::serializeFacetContent($facetContent[$facetCode], $action_type, $query_offset, $query_limit, $filter_state_id);

header("Content-Type: text/xml");
header("Character-Encoding: UTF-8");
echo $response;