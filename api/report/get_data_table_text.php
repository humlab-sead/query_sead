<?php
/*
file: get_data_table.php
this file makes download of data and documenation
A dialogbox is shown to allow the user to save the result file.
Sequence:
* Process the facet view state using <FacetConfigDeserializer::deserialize>
* Remove invalid selection using <deleteBogusPicks>
* Process the result parameter from the client using  <ResultConfigDeserializer::deserialize>
* Get the SQL-query using function  <compileQuery>
* Run the query and build the output
* Build the documentation of the facet filter using <FacetPicksSerializer::toHTML>
* Make  a zip-file for the documentation and datatable
*/

require_once(__DIR__ . '/../../server/config/environment.php');
require_once(__DIR__ . '/../../server/connection_helper.php');
require_once(__DIR__ . '/../cache_helper.php');
require_once(__DIR__ . '/../../server/result_sql_compiler.php');
require_once(__DIR__ . "/../serializers/facet_config_deserializer.php");
require_once(__DIR__ . "/../serializers/result_config_deserializer.php");
require_once(__DIR__ . "/../serializers/facet_picks_serializer.php");

ConnectionHelper::openConnection();

$facetXml = CacheHelper::get_facet_xml($_REQUEST['cache_id']);
$resultXml = CacheHelper::get_result_xml($_REQUEST['cache_id']);
$facetsConfig = FacetConfigDeserializer::deserialize($facetXml)->deleteBogusPicks();
$resultConfig = ResultConfigDeserializer::deserialize($resultXml);

$q = ResultSqlQueryCompiler::compile($facetsConfig, $resultConfig);

if (empty($q)) {
    exit;
}

$text_table_doc = " data hämtat ur databas genom följande operation \n" . $q . "\n";

$rs = ConnectionHelper::query($q);

$delimiter = "\t";

$item_counter = 1;
$use_count_item = false;
$html_doc = "";
foreach ($resultConfig->items as $headline) {
    if ($item_counter == 1 && $headline != "parish_level") {
        $use_count_item = true;
    }
    $item_counter++;
    foreach (ResultDefinitionRegistry::getDefinition($headline)->fields as $res_def_key => $definition_item) {
        foreach ($definition_item as $item) {
            if ($res_def_key != 'sort_item' && !($res_def_key == 'count_item' && !$use_count_item)) {
                $extra_text_info = ($res_def_key == 'count_item' && $use_count_item) ?  " Number of items with a value " : "";
                $html_doc .= " <BR>{$item->text}$extra_text_info<BR>";
                $text_table .= "{$item->text}$extra_text_info";
                $text_table .= $delimiter;
            }
        }
    }
}
$text_table .= "\n";

while (($row = pg_fetch_assoc($rs))) {
    foreach ($row as $row_item) {
        $text_table .= $row_item . $delimiter;
    }
    $text_table .= "\n";
}

ConnectionHelper::closeConnection();

$selection_html = "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">";
$selection_html .= "<HTML>";
$selection_html .= "<HEAD>";
$selection_html .= " <TITLE>SEAD - Search variables description</TITLE>";
$selection_html .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
$selection_html .= "<link rel=\"stylesheet\" type=\"text/css\" href= \"/client/theme/style.css\"  />";
$selection_html .= "</HEAD>";
$selection_html .= " <BODY><h1>SEAD - Search variables description</h1><BR>";
$selection_html .= " Aggregation level<BR>";
$selection_html .= ResultDefinitionRegistry::getDefinition($resultConfig->aggregation_code)->text;
$selection_html .= " <BR>Selected result variables:<BR>";
$selection_html .= $html_doc;
$selection_html .= "<BR>Current filter:<br>";
$selection_html .= "";
$selection_html .= "SQL-query:<br>" . $q;
$selection_html .= "</BODY>";
$selection_html .= "</HTML>";

// Create a zip file consisting of one html-file containing the selections made, and one text-file containing the search result.
$zip = new ZipArchive();

$filename = "cache/zip_" . $_REQUEST['cache_id'] . "_data_and_docs.zip";
$file_name_user = $_REQUEST['cache_id'] . "_data_and_docs.zip";
if ($zip->open($filename, ZipArchive::OVERWRITE) !== TRUE) {
    exit("cannot open <$filename>\n");
}

$zip->addFromString("result_data.txt", $text_table);
$zip->addFromString("documentation.html", $selection_html);
$zip->close();

if (array_key_exists('link_only', $_REQUEST) && !empty($_REQUEST['link_only'])) {
    $zip_data = file_get_contents($filename);
    header("Character-Encoding: UTF-8");
    header('Content-type: zip/binary');
    header('Content-disposition: attachment; filename="' . $file_name_user . '"');
    echo $zip_data;
} else {
    echo $filename;
}

?>