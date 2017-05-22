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
require_once(__DIR__ . "/cache_helper.php");
require_once(__DIR__ . "/../server/category_distribution_loader.php");
require_once(__DIR__ . "/serializers/facet_definition_serializer.php");

ConnectionHelper::openConnection();

global $facet_definition;

$bounds = CacheHelper::get_range_category_bounds();
if (empty($data)) {
    $bounds = RangeCategoryBoundsLoader::load();
    CacheHelper::put_range_category_bounds($bounds);
}

$out = FacetDefinitionSerializer::toJSON($facet_definition, $bounds);

ConnectionHelper::closeConnection();
 
echo $out;

?>