<?php

require_once(__DIR__ . "/../server/config/bootstrap_application.php");
require_once(__DIR__ . "/../server/connection_helper.php");
require_once(__DIR__ . "/../server/lib/Cache.php");
require_once(__DIR__ . "/../server/facet_config.php");
require_once(__DIR__ . "/serializers/facet_config_deserializer.php");
require_once(__DIR__ . "/serializers/facet_picks_serializer.php");

$xml = $_REQUEST["xml"] ?: "";

ConnectionHelper::openConnection();

$facetsConfig = FacetConfigDeserializer::deserialize($xml)->deleteBogusPicks();
$matrix       = $facetsConfig->collectUserPicks();
$tooltip      = ($matrix['count']['discrete'] > 0) ? FacetPicksSerializer::toHTML($matrix) : "";

ConnectionHelper::closeConnection();

header("Content-Type: text/xml");

// FIXME: Move to serializer (add args)
echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
echo "<data>";
echo "<f_code>", $facetsConfig->targetCode, "</f_code>\n";
echo "<report_html><![CDATA[", $tooltip, "]]></report_html>\n";
echo "<count_of_selections>", $matrix['count']['discrete'], "</count_of_selections>\n";
echo "</data>";
