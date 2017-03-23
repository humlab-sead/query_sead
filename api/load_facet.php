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
* Process facet_params using <fb_process_params> and build compoosite array of the facet_xml-document being posted from the client
* Remove invalid selections using <remove_invalid_selections> since selections are keep at the client altough the might be filter out.
* Derive a compsosite ID for caching of the facet content.
* Render the data for the facet using function <get_facet_content>
* Computing start_row and limit if the text-search is being used.
* Output the parts of the facet's data to the client using <FacetXMLSerializer::build_xml_response>, depending on how much data the client requests or defined by text-search start_row
*/

require_once __DIR__ . '/../server/connection_helper.php';
require_once(__DIR__ . "/../server/fb_server_funct.php");
include_once(__DIR__ . "/../server/lib/Cache.php");
require_once(__DIR__ . "/../server/facet_content_loader.php");

if (!empty($_REQUEST["xml"])) {
    $xml=$_REQUEST["xml"];
}

$load_options = fb_process_params($xml);

$conn = ConnectionHelper::createConnection();

#$f_code     = $load_options["f_action"][0];
$f_action      = $load_options["f_action"][1];
$f_code        = $load_options["requested_facet"];
$facet         = $facet_definition[$f_code];
$facet_type    = $facet["facet_type"];
$facet_options = $load_options["facet_collection"][$f_code];
$load_options  = remove_invalid_selections($conn, $load_options);

function computeCacheKey($load_options)
{
    global $filter_by_text;
    $f_code = $load_options["requested_facet"];
    $flist_str = implode("", getKeysOfActiveFacets($load_options));
    $filter = $filter_by_text ? $load_options["facet_collection"][$f_code]["facet_text_search"] : "no_text_filter";
    return $f_code.$flist_str.generateUserSelectItemsCacheId($load_options).$load_options["client_language"].$filter;
}

$cache_id = computeCacheKey($load_options);

if (!($f_content = DataCache::Get("_".$load_options["client_language"].$applicationName, $cache_id))) {
    $f_content=$facet_content_loaders[$facet_type]->get_facet_content($conn, $load_options);
    DataCache::Put("_".$load_options["client_language"].$applicationName, $cache_id, 1500, $f_content);
}

// Make a list of selection to exclude
// if the action type is populate with text search then do the following, otherwise just do as normal... scroll or selection change

$query_offset=$facet_options["facet_start_row"];
$query_limit=$facet_options["facet_number_of_rows"];

$action_type=$load_options["f_action"][1];
if ($action_type=="populate_text_search") {
    // get the textfilter where to position the requested facet
    // skip scroll to row if filter_by_text is true?
    
    $find_str = $facet_options["facet_text_search"];
    $facet_rows=$f_content[$f_code]["rows"];
    $start_row=find_start_row($facet_rows, $find_str); // start in the center of the loadin
    
    $query_offset=$start_row;
    
    if ($query_offset>$f_content[$f_code]['total_number_of_rows']-12) {
        $query_offset=$f_content[$f_code]['total_number_of_rows']-12;
    }
    if ($query_offset<=0) {
        $query_offset=0;
    }
}

if ($facet_type=="range") {
    $query_offset=0;
    $query_limit=250;
}

$response = FacetXMLSerializer::build_xml_response($f_content[$f_code], $action_type, $duration, $query_offset, $query_limit, $filter_state_id);
header("Content-Type: text/xml");

echo $response;

pg_close($conn);