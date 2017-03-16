<?php

require_once('fb_server_funct.php');
include_once ("lib/Cache.php");

if (!empty($_REQUEST["xml"])) {
    $xml=$_REQUEST["xml"];
}

$facet_params = fb_process_params($xml);

if (!($conn = pg_connect(CONNECTION_STRING))) {
    echo "Error: pg_connect failed.\n";
    exit;
}

$facet_params=remove_invalid_selections($conn, $facet_params);

$f_code = $facet_params["requested_facet"];

$tooltip_text = "";
$count_of_selections = derive_count_of_selection($facet_params, $f_code);
if ($count_of_selections != 0) {
    $tooltip_text = derive_selections_to_html($facet_params, $f_code);
}

$xml = "<?xml version=\"1.0\" encoding=\"utf-8\" ?>";
$xml .= "<data>";
$xml .= "<f_code>" . $f_code . "</f_code>\n";
$xml .= "<report_html><![CDATA[" .$tooltip_text . "]]></report_html>\n";
$xml .= "<count_of_selections>" . $count_of_selections . "</count_of_selections>\n";
$xml .= "</data>";

header("Content-Type: text/xml");

echo $xml;
