<?php

require_once(__DIR__ . "/../server/config/bootstrap_application.php");
require_once(__DIR__ . "/../server/connection_helper.php");
require_once(__DIR__ . "/../server/lib/Cache.php");
require_once(__DIR__ . "/../server/facet_config.php");

if (!empty($_REQUEST["xml"])) {
    $xml = $_REQUEST["xml"];
}

$facetConfig = FacetConfigDeserializer::deserializeFacetConfig($xml);
$conn = ConnectionHelper::createConnection();
$facetConfig = FacetConfig::removeInvalidUserSelections($conn, $facetConfig);
$facetCode = $facetConfig["requested_facet"];

$tooltip = "";
$selectCount = FacetConfig::computeUserSelectItemCount($facetConfig, $facetCode);
if ($selectCount != 0) {
    $tooltip = FacetConfig::generateUserSelectItemHTML($facetConfig, $facetCode);
}

$xml  = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
$xml .= "<data>";
$xml .= "<f_code>" . $facetCode . "</f_code>\n";
$xml .= "<report_html><![CDATA[" .$tooltip . "]]></report_html>\n";
$xml .= "<count_of_selections>" . $selectCount . "</count_of_selections>\n";
$xml .= "</data>";

header("Content-Type: text/xml");
echo $xml;
