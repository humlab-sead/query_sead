<?php
/*
file: js_facet_def.php
This file returns the facet_defintion to the client in javas-script format

Information of the facets are defined in

* <fb_def.php (SHIPS)>
* <fb_def.php (SEAD)>
* <fb_def.php (diabas)>

id  - id of the facet
name -  name of the facet which will be use used a the title
display_title - same a name above
facet_type - which type of facet, eg discrete, range or geo
default - defines whether it should be loaded automatically
category - for grouping of facets in facet control area

*/
error_reporting( error_reporting() & ~E_NOTICE );
require_once("applications/applicationSpecification.php");
require_once('fb_server_funct.php');
include_once("server/lib/Cache.php");

// compute max and min for range facet
function compute_max_min($conn)
{
    global $facet_definition;
    $q="";
    foreach ($facet_definition as $f_code => $element)
    {
        if ($element["facet_type"]=="range")
        {
            $query_column=$element["id_column"];
            $query_table = $facet_definition[$f_code]["table"];
            $data_tables[] = $query_table;
            //check if f_code exist in list, if not add it. since counting can be done also in result area and then using "abstract facet" that are not normally part of the list
            $f_list[] = $f_code;
            // use the id's to compute direct resource counts
            // filter is not being used be needed as parameter
            $query = get_query_clauses($params, $f_code, $data_tables, $f_list);
            
            $extra_join = isset($query["joins"]) ? $query["joins"] . "  " : "";

            $q.=" select '$f_code' as f_code,max(".$query_column."::real) as max,  min(".$query_column."::real) as min from ".$query_table. "  ". $extra_join;
            
            if ($facet_definition[$f_code]["query_cond"]!="")
            {
                $q.=" where ".$facet_definition[$f_code]["query_cond"]. "  ";
            }
            $q.="    union ";
        }
    }
    $q=substr($q,0,-7);
    
    if ($q != "")
    {
        if (($rs2 = pg_exec($conn, $q)) <= 0) { echo "Error: cannot execute   $q  \n"; pg_close($conn); exit; }
        while ($row = pg_fetch_assoc($rs2))
        {
            $facet_range[$row["f_code"]]["max"]=$row["max"];
            $facet_range[$row["f_code"]]["min"]=$row["min"];
        }
    }
    return $facet_range;
}

if (!($conn = pg_connect(CONNECTION_STRING))) { echo "Error: pg_connect failed.\n"; exit; }

if (!$facet_range = DataCache::Get("facet_min_max".$applicationName,"facet_range_data")) {
    $facet_range=compute_max_min($conn);
    DataCache::Put("facet_min_max".$applicationName, "facet_range_data", 1500,$facet_range);
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
        
        if (isset($element["counting_title"]) && !empty ($element["counting_title"]))
        {
            $out .= "\tfacets[$i][\"counting_title\"] = \"".$element["counting_title"]."\";\n";
        }
        else {
            $out .= "\tfacets[$i][\"counting_title\"] =\"Antal observationer\";\n";
        }
        $out .= "\tfacets[$i][\"color\"] = \"003399\";\n";
        $out .= "\tfacets[$i][\"facet_type\"] = \"".$element["facet_type"]."\";\n";
        if (isset($facet_range[$facet_key]))
        {
            $out .= "\tfacets[$i][\"max\"] = \"".$facet_range[$facet_key]["max"]."\";\n";
            $out .= "\tfacets[$i][\"min\"] = \"".$facet_range[$facet_key]["min"]."\";\n";
        }
        $out .= "\tfacets[$i][\"default\"] = \"".$element["default"]."\";\n";
        if (!isset($element["use_text_search"]))
            $out .= "\tfacets[$i][\"use_text_search\"] = \"1\";\n";
        else
            $out .= "\tfacets[$i][\"use_text_search\"] = \"".$element["use_text_search"]."\";\n";
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