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
* Process facetConfig using <FacetConfigDeserializer::deserialize> and build compoosite array of the facet_xml-document being posted from the client
* Remove invalid selections using <deleteBogusPicks> since selections are keep at the client altough the might be filter out.
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

// FIXME: Move to ContentService 
function computePage($facetsConfig, $facetContent)
{
    if ($facetsConfig->targetFacet->isOfType("range")) {
        return [0, 250];
    }
    $offset = $facetsConfig->targetConfig->startRow;
    $limit = $facetsConfig->targetConfig->rowCount;
    if ($facetsConfig->requestType == "populate_text_search") {
        $offset = ArrayHelper::findIndex($facetContent->rows, $facetsConfig->targetConfig->textFilter);
        $offset = max(0, min($offset, $facetContent->totalRowCount - 12));
    }
    return [$offset, $limit];
}

ConnectionHelper::openConnection();
$xml = (!empty($_REQUEST["xml"])) ? $_REQUEST["xml"] : NULL;
$facetsConfig = FacetConfigDeserializer::deserialize($xml)->deleteBogusPicks();
$facetContent = FacetContentService::load($facetsConfig);
ConnectionHelper::closeConnection();
$page = computePage($facetsConfig, $facetContent);
$response = FacetContentSerializer::serializeFacetContent($facetContent, $facetsConfig->requestType, $page[0], $page[1]);
ConnectionHelper::closeConnection();
header("Content-Type: text/xml");
header("Character-Encoding: UTF-8");
echo $response;