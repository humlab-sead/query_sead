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

require_once(__DIR__ . "/../server/fb_server_funct.php");
include_once(__DIR__ . "/../server/lib/Cache.php");

if (!empty($_REQUEST["xml"])) {
    $xml=$_REQUEST["xml"];
}

$facet_params = fb_process_params($xml);

if (!($conn = pg_connect(CONNECTION_STRING))) {
    echo "Error: pg_connect failed.\n";
    exit;
}

$f_code   = $facet_params["f_action"][0];
$f_action = $facet_params["f_action"][1];

$type_of_facet_requested=$facet_definition[$facet_params["requested_facet"]]["facet_type"];

// check if there are selections that are not within the requested facet set of values
$facet_params=remove_invalid_selections($conn, $facet_params);
$flist_str="";
$f_list=derive_facet_list($facet_params);
foreach ($f_list as $element) {
    $flist_str.=$element;
}

// check if the requested facet is the last facet
$position_of_request_facet=$facet_params["facet_collection"][$facet_params["requested_facet"]]["facet_position"];

$f_str=$facet_params["requested_facet"].$flist_str.derive_selections_string($facet_params).$facet_params["client_language"]."no_text_filter";

if ($filter_by_text) {
    $f_str.=$facet_params["facet_collection"][$facet_params["requested_facet"]]["facet_text_search"];
}

if (!($f_content = DataCache::Get("_".$facet_params["client_language"].$applicationName, $f_str))) {
    $f_content=get_facet_content($conn, $facet_params);
    DataCache::Put("_".$facet_params["client_language"].$applicationName, $f_str, 1500, $f_content);
}

// Make a list of selection to exclude
// if the action type is populate with text search then do the following, otherwise just do as normal... scroll or selection change

$query_offset=$facet_params["facet_collection"][$facet_params["requested_facet"]]["facet_start_row"];
$query_limit=$facet_params["facet_collection"][$facet_params["requested_facet"]]["facet_number_of_rows"];

$action_type=$facet_params["f_action"][1];
if ($action_type=="populate_text_search") {
    // get the textfilter where to position the requested facet
    // skip scroll to row if filter_by_text is true?
    
    $find_str = $facet_params["facet_collection"][$facet_params["requested_facet"]]["facet_text_search"];
    $facet_rows=$f_content[$facet_params["requested_facet"]]["rows"];
    $start_row=find_start_row($facet_rows, $find_str); // start in the center of the loadin
    
    $query_offset=$start_row;
    
    if ($query_offset>$f_content[$facet_params["requested_facet"]]['total_number_of_rows']-12) {
        $query_offset=$f_content[$facet_params["requested_facet"]]['total_number_of_rows']-12;
    }
    if ($query_offset<=0) {
        $query_offset=0;
    }
}

if ($type_of_facet_requested=="range") {
    $query_offset=0;
    $query_limit=250;
} elseif ($type_of_facet_requested=="geo") {
    $query_offset=0;
    
    $f_code=$facet_params["requested_facet"];
    $f_selected=derive_selections($facet_params);
    $geo_selection=$f_selected[$f_code]["selection_group"];
    $geo_boxes=get_geo_box_from_selection($geo_selection);
    $query_limit=$geo_boxes["count_boxes"];
}

$response = FacetXMLSerializer::build_xml_response($f_content[$facet_params["requested_facet"]], $action_type, $duration, $query_offset, $query_limit, $filter_state_id);
header("Content-Type: text/xml");

echo $response;

pg_close($conn);