<?php
/*
file: get_data_table.php
this file makes download of data and documenation
A dialogbox is shown to allow the user to save the result file.
Sequence:
* Process the facet view state using <FacetConfigDeserializer::deserializeFacetConfig>
* Remove invalid selection using <FacetConfig::removeInvalidUserSelections>
* Process the result parameter from the client using  <ResultConfigDeserializer::deserializeResultConfig>
* Get the SQL-query using function  <compileQuery>
* Run the query and build the output
* Build the documentation of the facet filter using <FacetConfig::generateUserSelectItemHTML>
* Make  a zip-file for the documentation and datatable
*/

require_once (__DIR__ . '/../server/connection_helper.php');
require_once (__DIR__ . '/../server/cache_helper.php');
require_once (__DIR__ . '/../server/result_query_compiler.php');

$conn = ConnectionHelper::createConnection();

$facet_xml = CacheHelper::get_facet_xml_from_id($_REQUEST['cache_id']);
$facet_params = FacetConfigDeserializer::deserializeFacetConfig($facet_xml);
$facet_params=FacetConfig::removeInvalidUserSelections($conn,$facet_params);
$result_xml = get_result_xml_from_id($_REQUEST['cache_id']);
$resultConfig = ResultConfigDeserializer::deserializeResultConfig($result_xml);
$aggregation_code = $resultConfig["aggregation_code"];

$q = ResultQueryCompiler::compileQuery($facet_params, $resultConfig);

if (empty($q)) {
    exit;
}

$text_table_doc=" data hämtat ur databas genom följande operation \n".$q."\n";

if (($rs = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query3. $q \n"; exit; }

$delimiter="\t";

$item_counter=1;
$use_count_item=false;
foreach($resultConfig["items"] as $headline)
{
    if ($item_counter==1 && $headline!="parish_level")
    {
        $use_count_item=true;
    }
    $item_counter++;
    // First create header for the column.
    foreach ($result_definition[$headline]["result_item"] as $res_def_key =>$definition_item)
    {
        foreach ($definition_item as $item_type =>$item)
        {
            if ($res_def_key!='sort_item'  && !($res_def_key=='count_item' && !$use_count_item )  )
            {
                // add (counting phras for counting variables)
                if ($res_def_key=='count_item' && $use_count_item)
                {
                    $extra_text_info= " ".t("(antal med värde)", $facet_params["client_language"]). " ";
                    $html_doc.=" <BR>".t($item["text"], $facet_params["client_language"]).$extra_text_info."<BR>";
                    $text_table.=t($item["text"], $facet_params["client_language"]).$extra_text_info;
                    $text_table.=$delimiter;
                } else {
                    $html_doc.="<BR> ".t($item["text"],$facet_params["client_language"])."<BR>";
                    $text_table.=t($item["text"], $facet_params["client_language"]);
                    $text_table.=$delimiter;
                }
            }
        }
    }
}
$text_table.= "\n";

while (($row = pg_fetch_assoc($rs) ))
{
    foreach($row as $row_item)
    {
        $text_table.= $row_item.$delimiter;
    }
    $text_table.="\n";
}

global $result_definition,$application_name, $applicationTitle, $applicationTitle;
//$result_definition
$selection_html="<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">";
$selection_html.="<HTML>";
$selection_html.= "<HEAD>";
$selection_html.= " <TITLE> $applicationTitle - ".t("beskrivning av sökparametrar", $facet_params["client_language"])."</TITLE>";
$selection_html.= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">";
$selection_html.= "<link rel=\"stylesheet\" type=\"text/css\" href= \"/client/theme/style.css\"  />";
$selection_html.= "</HEAD>";
$selection_html.=" <BODY><h1> $applicationTitle - ".t("beskrivning av sökparametrar", $facet_params["client_language"])."</h1><BR>";
$selection_html.=" <h2>".t("Summeringsnivå : ", $facet_params["client_language"])."</h2><BR>";
$selection_html.=t($result_definition[$aggregation_code]["text"],$facet_params["client_language"]);
$selection_html.=" <BR><h2>".t("Valda resultvariabler :", $facet_params["client_language"])." </h2><BR>";
$selection_html.=$html_doc;
$selection_html.="<BR><h2>".t("Aktuella filter: ",$facet_params["client_language"])."</h2>";
$selection_html.=FacetConfig::generateUserSelectItemHTML($facet_params);
$selection_html.="<h2>".t("SQL-fråga: ",$facet_params["client_language"])." </h2>".$q;
$selection_html.="</BODY>";
$selection_html.="</HTML>";

// Create a zip file consisting of one html-file containing the selections made, and one text-file containing the search result.
$zip = new ZipArchive();

$filename = "cache/zip_".$_REQUEST['cache_id']."_data_and_docs.zip";
$file_name_user=$_REQUEST['cache_id']."_data_and_docs.zip";
if ($zip->open($filename, ZIPARCHIVE::OVERWRITE)!==TRUE) {
    exit("cannot open <$filename>\n");
}

$zip->addFromString("result_data.txt"  , $text_table);
$zip->addFromString("documentation.html" , $selection_html);
$zip->close();

if (!isset($_REQUEST['link_only']))
{
    $zip_data=file_get_contents($filename);
    header("Character-Encoding: UTF-8");
    header('Content-type: zip/binary');
    header('Content-disposition: attachment; filename="'.$file_name_user.'"');
    echo $zip_data;
}
else
{
    echo $filename;
}

?>