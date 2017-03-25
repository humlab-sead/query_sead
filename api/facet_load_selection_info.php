<?php

// require_once('fb_server_funct.php');
// include_once ("lib/Cache.php");

require_once(__DIR__ . "/../server/fb_server_funct.php");
require_once(__DIR__ . "/../server/lib/Cache.php");
require_once(__DIR__ . "/../server/connection_helper.php");

if (!empty($_REQUEST["xml"])) {
    $xml=$_REQUEST["xml"];
}

$facetConfig = FacetConfigDeserializer::deserializeFacetConfig($xml);

$conn = ConnectionHelper::createConnection();

$facetConfig = FacetConfig::removeInvalidUserSelections($conn, $facetConfig);

$f_code = $facetConfig["requested_facet"];

$tooltip_text = "";
$count_of_selections = FacetConfig::computeUserSelectItemCount($facetConfig, $f_code);
if ($count_of_selections != 0) {
    $tooltip_text = FacetConfig::generateUserSelectItemHTML($facetConfig, $f_code);
}

$xml = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
$xml .= "<data>";
$xml .= "<f_code>" . $f_code . "</f_code>\n";
$xml .= "<report_html><![CDATA[" .$tooltip_text . "]]></report_html>\n";
$xml .= "<count_of_selections>" . $count_of_selections . "</count_of_selections>\n";
$xml .= "</data>";

header("Content-Type: text/xml");

echo $xml;
