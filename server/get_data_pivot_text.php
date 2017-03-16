<?php
/*
file: get_data_pivot_text.php
this file makes download of data and documenation
A dialogbox is shown to allow the user to save the result file.
Sequence:
* Process the facet view state using <fb_process_params>
* Remove invalid selection using <remove_invalid_selections>
* Process the result parameter from the client using  <process_result_params>
##* Get the SQL-query using function  <get_result_data_query>
* Run the query and build the output
* Build the documentation of the facet filter using <derive_selections_to_html>
* Make  a zip-file for the documentation and datatable
*/

require_once('fb_server_funct.php');

if (!($conn = pg_connect(CONNECTION_STRING))) { echo "Error: pg_connect failed.\n"; exit; }

// The xml-data is containing facet information is processed and all parameters are put into an array for futher use.

$facet_xml_file_location="cache/".$_REQUEST['cache_id']."_facet_xml.xml";
$facet_xml=file_get_contents($facet_xml_file_location);

$facet_params = fb_process_params($facet_xml);
$facet_params=remove_invalid_selections($conn,$facet_params);

$result_xml_file_location="cache/".$_REQUEST['cache_id']."_result_xml.xml";
$result_xml=file_get_contents($result_xml_file_location);

$result_params = process_result_params($result_xml);

$aggregation_code=$result_params["aggregation_code"];

$f_code="taxon_result";
$data_tables[]="tbl_sites";
$data_tables[]="tbl_physical_samples";
$data_tables[]="tbl_datasets";
$data_tables[]="tbl_analysis_entities";
$data_tables[]="tbl_abundance";

$tmp_list=derive_facet_list($facet_params);
//Add result_facet as final facet
$tmp_list[]=$f_code;

$query = get_query_clauses( $facet_params, $f_code, $data_tables,$tmp_list);
$extra_join=$query["joins"];
$table_str=$query["tables"];
if ($extra_join!="")
    $and_command=" and  ";

$q.="select  tbl_abundance.taxon_id, tbl_abundance.abundance  from ".$table_str."  where 1=1 $and_command $extra_join $filter ";

if ($query["where"]!='')
{
    $q.=" and  ".$query["where"];
}

if (($rs = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query3. $q \n"; exit; }

while (($row = pg_fetch_assoc($rs) ))
{
    foreach($row as $row_item)
    {
        $text_table.= $row_item.$delimiter;
    }
    $text_table.="\n";
}
?>