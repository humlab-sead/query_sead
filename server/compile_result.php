<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once(__DIR__ . "/config/environment.php");
require_once(__DIR__ . "/config/bootstrap_application.php");
require_once(__DIR__ . '/connection_helper.php');
require_once(__DIR__ . '/facet_config.php');
require_once(__DIR__ . '/language/t.php');
require_once(__DIR__ . '/query_builder.php');
require_once(__DIR__ . '/result_query_compiler.php');
require_once __DIR__ . '/facet_content_counter.php';

/*
* Funnction: *  render_column_meta_data
* Copy definition data
*/
function render_column_meta_data($result_definition, $resultConfig, $facetConfig)
{
    $data_item_counter = 0;
    foreach ($resultConfig["items"] as $result_params_key) {
        // First create header for the column.
        foreach ($result_definition[$result_params_key]["result_item"] as $result_column_type => $result_definition_item) {
            foreach ($result_definition_item as $item_type => $result_item) {
                if ($result_column_type!="sort_item") {
                    $column_meta_data[$data_item_counter]["result_column_type"]=$result_column_type;
                    $column_meta_data[$data_item_counter]["link_url"]=$result_item["link_url"];
                    $column_meta_data[$data_item_counter]["link_label"]=$result_item["link_label"];
                    if ($result_column_type == 'count_item') {
                        $extra_text_info = " <BR>" . t("(antal med värde)", $facetConfig["client_language"]) . " ";
                        $column_meta_data[$data_item_counter]["result_column_title"]=t($result_item["text"], $facetConfig["client_language"]) ;
                        $column_meta_data[$data_item_counter]["result_column_title_extra_info"]=$extra_text_info;
                    } else {
                        $column_meta_data[$data_item_counter]["result_column_title"]=t($result_item["text"], $facetConfig["client_language"]) ;
                        $column_meta_data[$data_item_counter]["result_column_title_extra_info"]="";
                    }
                    $data_item_counter++;
                }
            }
        }
    }
    return $column_meta_data;
}

//***************************************************************************************************************************************************
/*
Function: render_html
Function for retrieving data for the result list and transforming that data into an html table.
Parameters:
$facetConfig -  An array containing info about the current view-status.
$resultConfig -  An array containing the statistic variables the user wants to show data for.
*/

class RenderResultListXML {
    public static function render_column_meta_data($result_definition, $resultConfig, $facetConfig)
    {
        $data_item_counter = 0;
        foreach ($resultConfig["items"] as $result_params_key) {
            // First create header for the column.
            foreach ($result_definition[$result_params_key]["result_item"] as $result_column_type => $result_definition_item) {
                foreach ($result_definition_item as $item_type => $result_item) {
                    if ($result_column_type!="sort_item") {
                        $column_meta_data[$data_item_counter]["result_column_type"]=$result_column_type;
                        $column_meta_data[$data_item_counter]["link_url"]=$result_item["link_url"];
                        $column_meta_data[$data_item_counter]["link_label"]=$result_item["link_label"];
                        if ($result_column_type == 'count_item') {
                            $extra_text_info = " <BR>" . t("(antal med värde)", $facetConfig["client_language"]) . " ";
                            $column_meta_data[$data_item_counter]["result_column_title"]=t($result_item["text"], $facetConfig["client_language"]) ;
                            $column_meta_data[$data_item_counter]["result_column_title_extra_info"]=$extra_text_info;
                        } else {
                            $column_meta_data[$data_item_counter]["result_column_title"]=t($result_item["text"], $facetConfig["client_language"]) ;
                            $column_meta_data[$data_item_counter]["result_column_title_extra_info"]="";
                        }
                        $data_item_counter++;
                    }
                }
            }
        }
        return $column_meta_data;
    }

    private static function render_data_rows_as_array($conn, $rs, $max_result_display_rows, $column_meta_data, $cache_id)
    {
        $row_counter=0;
        while (($row = pg_fetch_assoc($rs) ) && ($row_counter < $max_result_display_rows )|| 1==0) {
            $column_counter=0;
            $row_key="".$row_counter;
            foreach ($row as $row_item) {
                $skip_column=false;
                $data_array[$row_key]["row_id"]=$row_counter;
                switch ($column_meta_data[$column_counter]["result_column_type"]) {
                    case "link_item":
                        $url=$column_meta_data[$column_counter]["link_url"];
                        $data_array[$row_key][$column_counter]["link_url"]="".$url."=".$row_item."";
                        break;
                    case "link_item_filtered":
                        $url=$column_meta_data[$column_counter]["link_url"];
                        $row_text="<A HREF=\"$url=$row_item&cache_id=$cache_id\" title=\"info\" target=\"blank\" >$row_item</A>";
                        break;
                    case "sort_item":
                        $skip_column=true;
                        break;
                    default:
                        $row_text=$row_item;
                        $data_array[$row_key][$column_counter]["row_text"]=$row_text;
                        break;
                }
                // add formattting for html-link items
                if (!$skip_column) {
                    $data_array[$row_key][$column_counter]["cell_type"]=$column_meta_data[$column_counter]["result_column_type"];
                    $data_array[$row_key][$column_counter]["result_column_title"]=$column_meta_data[$column_counter]["result_column_title"];
                    $data_array[$row_key][$column_counter]["result_column_title_extra"]=$column_meta_data[$column_counter]["result_column_title_extra"];
                    $column_counter++; // this counter is used to know the type of result_item
                }
            }
            $row_counter++;
        }
        return $data_array;
    }

    private static function xml_encode($array, $indent = false, $i = 0)
    {
        if (!$i) {
            /*   $data = ''.($indent?"\r\n":'').'<root>'.($indent?"\r\n":'');*/
        } else {
            $data = '';
        }
        foreach ($array as $k => $v) {
            if (is_numeric($k)) {
                $k = 'item';
            }
            $data .= ($indent?str_repeat("\t", $i):'').'<'.$k.'>';
            if (is_array($v)) {
                $data .= ($indent?"\r\n":'').xml_encode($v, $indent, ($i+1)).($indent?str_repeat("\t", $i):'');
            } else {
                $data .= "<![CDATA[".$v."]]>";
            }
            $data .= '</'.$k.'>'.($indent?"\r\n":'');
        }
        return $data;
    }

    private static function render_result_array_as_xml($result_array)
    {
        return xml_encode($result_array);
    }

    public static function render_xml($conn, $facetConfig, $resultConfig, $data_link, $cache_id, $data_link_text)
    {
        global $facet_definition, $result_definition, $max_result_display_rows;
        $q = ResultQueryCompiler::compileQuery($facetConfig, $resultConfig). " ";
        if (empty($q)) {
            return "";
        }
        $rs = ConnectionHelper::query($conn, $q);
        $column_meta_data = self::render_column_meta_data($result_definition, $resultConfig, $facetConfig);
        $result_array = self::render_data_rows_as_array($conn, $rs, $max_result_display_rows, $column_meta_data, $cache_id);
        $result_data_xml = self::render_result_array_as_xml($result_array);
        return $result_data_xml;
    }
}

class RenderResultListHTML {

    private static function render_rows($conn, $rs, $max_result_display_rows, $header_data, $cache_id)
    {
        global $applicationName;
        $row_counter=0;
        while (($row = pg_fetch_assoc($rs) ) && ($row_counter < $max_result_display_rows )|| 1==0) {
            $html_table.= "<TR class=" . ($row_counter % 2 == 0 ? "evenrow" : "oddrow") .">";
            $column_counter=0;
            foreach ($row as $row_item) {
                $java_script="";
                $skip_column=false;
                switch ($header_data[$column_counter]["result_column_type"]) {
                    case "link_item":
                        $url=$header_data[$column_counter]["link_url"];
                        if (isset($header_data[$column_counter]["link_label"])) {
                            $link_label=$header_data[$column_counter]["link_label"];
                        } else {
                            $link_label=$row_item;
                        }
                        
                        $row_text="<A HREF=\"$url=$row_item&application_name=$applicationName\" title=\"info\" target=\"blank\" >$link_label</A>";
                        break;
                    case "link_item_filtered":
                        $url=$header_data[$column_counter]["link_url"];
                        if (isset($header_data[$column_counter]["link_label"])) {
                            $link_label=$header_data[$column_counter]["link_label"];
                        } else {
                            $link_label=$row_item;
                        }
                        
                        $row_text="<A HREF=\"$url=$row_item&cache_id=$cache_id&application_name=$applicationName\" title=\"info\" target=\"blank\" >$link_label</A>";
                        break;
                    case "sort_item":
                        $skip_column=true;
                        break;
                    default:
                        $row_text= $row_item;
                        break;
                }
                // add formattting for html-link items
                if (!$skip_column) {
                    $html_table.= "<td ".$java_script. ">".$row_text."</td>\n";
                    $column_counter++; // this counter is used to know the type of result_item
                }
            }
            $html_table.= "</TR>";
            $row_counter++;
        }
        return $html_table;
    }

    private static function result_render_list_view_ingress($tot_records, $tot_columns, $save_data_link_xls, $save_data_link_text) {
        global $max_result_display_rows;
        $use_xls = ($tot_records * $tot_columns) < 10000;
        $phrase = "Your search resulted in $tot_records records.";
        if ($tot_records > $max_result_display_rows)
            $phrase .= "The first $max_result_display_rows records are displayed below ";
        $phrase .= " <a href=\"$save_data_link_text\" id=\"download_link\" >Save data as text to file.</a>";
        if ($use_xls)
            $phrase .= "  <a href=\"$save_data_link_xls\" id=\"download_link2\">Save data to Excel.</a>";
        return $phrase;
    }

    private static function result_render_list_view_header_as_html($header_array)
    {
        foreach ($header_array as $item) {
            $html .= "<th>" . $item["result_column_title"] . " " . $item["result_column_title_extra_info"] . "</th>";
        }
        return $html;
    }

    public static function render_html($conn, $facetConfig, $resultConfig, $data_link, $cache_id, $data_link_text)
    {
        global $result_definition, $max_result_display_rows;
        $q = ResultQueryCompiler::compileQuery($facetConfig, $resultConfig);
        if (empty($q)) {
            return "";
        }
        $rs = ConnectionHelper::query($conn, $q);
        $tot_records = pg_num_rows($rs);
        $tot_columns = count($resultConfig["items"]);
        
        $table_ingres = self::result_render_list_view_ingress($tot_records, $tot_columns, "server/" . $data_link, "server/" .$data_link_text);
        $column_meta_data = RenderResultListXML::render_column_meta_data($result_definition, $resultConfig, $facetConfig);
        $header = self::result_render_list_view_header_as_html($column_meta_data);
        $table_body = self::render_rows($conn, $rs, $max_result_display_rows, $column_meta_data, $cache_id);

        $html = <<<EOS
            $table_ingres
            <!-- BEGIN SQL -->
            <!-- $q -->
            <!-- END SQL -->
            <table id='result_output_table'>
            <thead><tr>$header</tr></thead>
            <tbody>$table_body</tbody>
            <!-- data array  -->
            <!-- array -->     
            </table>
EOS;
        return $html;
    }
}

function result_render_map_view($conn,$facetConfig,$resultConfig,$facet_xml,$result_xml)
{
	global $facet_definition,$direct_count_table,$direct_count_column ;
	$f_code="map_result";

	$query_column = $facet_definition[$f_code]["id_column"];
	$name_column = $facet_definition[$f_code]["name_column"];
	
	$lat_column="latitude_dd";
	$long_column="longitude_dd";
	$tmp_list=FacetConfig::getKeysOfActiveFacets($facetConfig);
	$tmp_list[]=$f_code; 
	$interval=1;

	if (isset($direct_count_column) && !empty($direct_count_column)  ) 
	{
        $direct_counts = DiscreteFacetCounter::get_discrete_counts($conn,  $f_code,  $facetConfig,$interval, $direct_count_table,$direct_count_column);
        $filtered_direct_counts = $direct_counts["list"];
	}

    $no_selection_params = FacetConfig::eraseUserSelectItems($facetConfig);

	if (isset($direct_count_column) && !empty($direct_count_column)  ) 
	{
	    $direct_counts = DiscreteFacetCounter::get_discrete_counts($conn,  $f_code,  $no_selection_params,$interval, $direct_count_table,$direct_count_column);
		$un_filtered_direct_counts = $direct_counts["list"];
	}

	$query = QueryBuildService::compileQuery($facetConfig, $f_code, $data_tables, $tmp_list);
	$extra_join = $query["joins"];
	$query_tables = $query["tables"];
	$query_where = $query["where"] != '' ? " and " . $query["where"] : "";	

	$q.="select distinct $name_column as name, $lat_column, $long_column, $query_column as id_column " .
        "from $query_tables $extra_join " .
        "where 1 = 1 $query_where ";

	$rs = ConnectionHelper::query($conn, $q);

	$out.="<sql_info>";
	$out.="<![CDATA[".$q."]]>";
	$out.="</sql_info>";
	$out.="<points>";
	while (($row = pg_fetch_assoc($rs) )) 
	{
		$site_id_list[]=$row["id_column"];	
		$out.="<point>";
		$out.="<name><![CDATA[".$row["name"]."]]></name>";
		$out.="<id>".$row["id_column"]."</id>";
		$out.="<latitude><![CDATA[".$row[$lat_column]."]]></latitude>";
		$out.="<longitude><![CDATA[".$row[$long_column]."]]></longitude>";
		$out.="<filtered_count><![CDATA[".   $filtered_direct_counts[$row["id_column"]]  ."]]></filtered_count>";
		$out.="<un_filtered_count><![CDATA[".   $un_filtered_direct_counts[$row["id_column"]]  ."]]></un_filtered_count>";
		$out.="</point>";
	}
	$out.="</points>";
	return $out;
}

?>