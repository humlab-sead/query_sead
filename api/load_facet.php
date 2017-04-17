<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

/*
file: load_facet.php

Load facet content data. Currently supported facet types: dicrete facet, range facet

XML post:
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/facet_post_xml.html
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/facet_post.xsd

XML response:
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/facet_response_xml.html
http://dev.humlab.umu.se/frepalm/ships_test/xml_documentation/facet_response.xsd

* Deserialize request data toFacetsConfig object
* Remove invalid selections
* Compute/load facet content data
* Computing start_row and limit if the text-search is being used.
* Serialize parts of the facet's data to the client for requested window
*/

require_once __DIR__ . '/../server/connection_helper.php';
require_once __DIR__ . "/../server/lib/utility.php";
require_once __DIR__ . "/cache_helper.php";
require_once __DIR__ . "/../server/facet_content_loader.php";
require_once __DIR__ . "/serializers/facet_config_deserializer.php";
require_once __DIR__ . "/serializers/facet_content_serializer.php";

ConnectionHelper::openConnection();

// FIXME: Move to API Service
$facet_content_loaders = array(
    "discrete" => new DiscreteFacetContentLoader(),
    "range" => new RangeFacetContentLoader()
);

class FacetContentService {

    public static function load($facetsConfig)
    {
        global $facet_content_loaders;
        $cacheId = $facetsConfig->getCacheId();
        if (!($facetContent = CacheHelper::get_facet_content($cacheId))) {
            $loader = $facet_content_loaders[$facetsConfig->targetFacet->facet_type];
            $facetContent = $loader->get_facet_content($facetsConfig);
            CacheHelper::put_facet_content($cacheId, $facetContent);
        }
        return $facetContent;
    }
}

$xml = $_REQUEST["xml"] ?: NULL;
$facetsConfig = FacetConfigDeserializer::deserialize($xml)->deleteBogusPicks();
$facetContent = FacetContentService::load($facetsConfig);
$page = $facetContent->computeWindow();
$response = FacetContentSerializer::serializeFacetContent($facetContent, $facetsConfig->requestType, $page[0], $page[1]);

ConnectionHelper::closeConnection();

header("Content-Type: text/xml");
header("Character-Encoding: UTF-8");
echo $response;