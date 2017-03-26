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
* Process facet_params using <FacetConfigDeserializer::deserializeFacetConfig> and build compoosite array of the facet_xml-document being posted from the client
* Remove invalid selections using <FacetConfig::removeInvalidUserSelections> since selections are keep at the client altough the might be filter out.
* Derive a compsosite ID for caching of the facet content.
* Render the data for the facet using function <get_facet_content>
* Computing start_row and limit if the text-search is being used.
* Output the parts of the facet's data to the client using <FacetContentSerializer::serializeFacetContent>, depending on how much data the client requests or defined by text-search start_row
*/

require_once __DIR__ . '/../server/connection_helper.php';
require_once(__DIR__ . "/../server/fb_server_funct.php");
include_once(__DIR__ . "/../server/lib/Cache.php");
require_once(__DIR__ . "/../server/facet_content_loader.php");
require_once(__DIR__ . "/../server/facet_content_serializer.php");

$xml = (!empty($_REQUEST["xml"])) ? $_REQUEST["xml"] : NULL;

$facetConfig = FacetConfigDeserializer::deserializeFacetConfig($xml);

$conn = ConnectionHelper::createConnection();

$f_action      = $facetConfig["f_action"][1];
$f_code        = $facetConfig["requested_facet"];
$facet         = $facet_definition[$f_code];
$facet_type    = $facet["facet_type"];
$facet_options = $facetConfig["facet_collection"][$f_code];

$facetConfig   = FacetConfig::removeInvalidUserSelections($conn, $facetConfig);

function computeCacheKey($facetConfig)
{
    global $filter_by_text;
    $f_code = $facetConfig["requested_facet"];
    $flist_str = implode("", FacetConfig::getKeysOfActiveFacets($facetConfig));
    $filter = $filter_by_text ? $facetConfig["facet_collection"][$f_code]["facet_text_search"] : "no_text_filter";
    return $f_code.$flist_str.FacetConfig::generateUserSelectItemsCacheId($facetConfig).$facetConfig["client_language"].$filter;
}

$cache_id = computeCacheKey($facetConfig);

if (!($facetContent = DataCache::Get("_".$facetConfig["client_language"].$applicationName, $cache_id))) {
    $facetContent=$facet_content_loaders[$facet_type]->get_facet_content($conn, $facetConfig);
    DataCache::Put("_".$facetConfig["client_language"].$applicationName, $cache_id, 1500, $facetContent);
}

// Make a list of selection to exclude
// if the action type is populate with text search then do the following, otherwise just do as normal... scroll or selection change

$query_offset = $facet_options["facet_start_row"];
$query_limit = $facet_options["facet_number_of_rows"];
$action_type = $facetConfig["f_action"][1];

if ($action_type=="populate_text_search") {
    $find_str = $facet_options["facet_text_search"];
    $facet_rows = $facetContent[$f_code]["rows"];
    $query_offset = RowFinder::findIndex($facet_rows, $find_str);
    $query_offset = max(0, min($query_offset, $facetContent[$f_code]['total_number_of_rows'] - 12));
}

if ($facet_type == "range") {
    $query_offset = 0;
    $query_limit = 250;
}

$response = FacetContentSerializer::serializeFacetContent($facetContent[$f_code], $action_type, $query_offset, $query_limit, $filter_state_id);

header("Content-Type: text/xml");

echo $response;

pg_close($conn);