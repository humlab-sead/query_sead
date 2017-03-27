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
require_once("server/config/environment.php");
include_once("server/lib/Cache.php");
include_once("server/connection_helper.php");
require_once('server/query_builder.php');

// compute max and min for range facet
function compute_max_min($conn)
{
    global $facet_definition;
    $q = "";
    $u = "";
    foreach ($facet_definition as $f_code => $element)
    {
        if ($element["facet_type"] == "range")
        {
            $query_column=$element["id_column"];
            $query_table = $facet_definition[$f_code]["table"];
            $query_cond = $facet_definition[$f_code]["query_cond"];
            $data_tables[] = $query_table;
            $f_list[] = $f_code;

            $query = QueryBuildService::compileQuery($params, $f_code, $data_tables, $f_list);
            
            $extra_join = $query["joins"] ?? "";
            $where_clause = $query_cond != "" ? " where " . $query_cond . "  " : "";

            $q .= " $u select '$f_code' as f_code,max($query_column::real) as max, min($query_column::real) as min from $query_table $extra_join $where_clause ";
            $u  = "union";
        }
    }
    
    if ($q != "")
    {
        $rs2 = ConnectionHelper::execute($conn, $q);
        while ($row = pg_fetch_assoc($rs2))
        {
            $facet_range[$row["f_code"]]["max"]=$row["max"];
            $facet_range[$row["f_code"]]["min"]=$row["min"];
        }
    }
    return $facet_range;
}

$conn = ConnectionHelper::createConnection();

if (!$facet_range = DataCache::Get("facet_min_max".$applicationName,"facet_range_data")) {
    $facet_range=compute_max_min($conn);
    DataCache::Put("facet_min_max".$applicationName, "facet_range_data", 1500, $facet_range);
}

$out = "var facets = Array();\n";
$i=0;
$default_slots_num = 0;
foreach ($facet_definition as $facet_key => $element)
{
    if ($element["applicable"]==1)
    {
        $out .= "\tfacets[$i] = Array();\n";
        $out .= "\tfacets[$i][\"id\"] = \"$facet_key\";\n";
        $out .= "\tfacets[$i][\"name\"] = \"".$element["display_title"]."\";\n";
        $out .= "\tfacets[$i][\"display_title\"] = \"".$element["display_title"]."\";\n";
        
        if (isset($element["counting_title"]) && !empty($element["counting_title"]))
        {
            $out .= "\tfacets[$i][\"counting_title\"] = \"".$element["counting_title"]."\";\n";
        }
        else {
            $out .= "\tfacets[$i][\"counting_title\"] =\"Number of observations\";\n";
        }
        $out .= "\tfacets[$i][\"color\"] = \"003399\";\n";
        $out .= "\tfacets[$i][\"facet_type\"] = \"".$element["facet_type"]."\";\n";
        if (isset($facet_range[$facet_key]))
        {
            $out .= "\tfacets[$i][\"max\"] = \"".$facet_range[$facet_key]["max"]."\";\n";
            $out .= "\tfacets[$i][\"min\"] = \"".$facet_range[$facet_key]["min"]."\";\n";
        }
        $out .= "\tfacets[$i][\"default\"] = \"".$element["default"]."\";\n";

        $use_text_search = $element["use_text_search"] ?? "1";

        $out .= "\tfacets[$i][\"use_text_search\"] = \"$use_text_search\";\n";
        $out .= "\tfacets[$i][\"category\"] = \"".$element["category"]."\";\n";
        $out .= "\tfacets[$i][\"slot\"] = \"".$element["slot"]."\";\n";
        
        $parents = "[]";
        if(is_array($element["parents"])) {
            $parents = "[";
            foreach($element["parents"] as $k => $parent) {
                $parents .= "'".$parent."',";
            }
            $parents .="]";
        }
        $out .= "\tfacets[$i][\"parents\"] = ".$parents.";\n";
        $i++;
        if($element["default"]) {
            $default_slots_num++;
        }
    }
}

$out .= "var facet_default_slots_num = ".$default_slots_num.";";
echo $out;

?>