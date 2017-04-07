<?php
/*
file: get_facet_definitions.php
This file returns the facet_defintion to the client in javas-script format

Information of the facets are defined in
* <bootstrap_application.php>

id  - id of the facet
name -  name of the facet which will be use used a the title
display_title - same a name above
facet_type - which type of facet, eg discrete, range or geo
default - defines whether it should be loaded automatically
category - for grouping of facets in facet control area

*/
error_reporting( error_reporting() & ~E_NOTICE );
require_once(__DIR__ . "/../server/lib/Cache.php");
require_once(__DIR__ . "/../server/connection_helper.php");
require_once(__DIR__ . "/../server/cache_helper.php");
require_once(__DIR__ . "/../server/facet_content_counter.php");
require_once(__DIR__ . "/serializers/facet_definition_serializer.php");

$conn = ConnectionHelper::createConnection();

global $facet_definition;

$facet_range = CacheHelper::get_facet_min_max();
if (empty($data)) {
    $facet_range = DiscreteMinMaxFacetCounter::compute_max_min($conn);
    CacheHelper::put_facet_min_max($facet_range);
}

$out = FacetDefinitionSerializer::toJSON($facet_definition, $facet_range);
 
echo $out;

?>