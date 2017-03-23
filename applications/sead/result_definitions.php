<?php
/* 
file: result_definitions.php (SEAD)
o The definitions of the groups of result varibles and result variables

definition of result variables:
Has in the code the name "result_definition_item"

table - which table in the database to be used
column - which column to be used, should include the table name
text - label of the result_item

Definition of result composite item:
can have one or more result variables/result_items.

text - display name of the result defintion for the result control bar
applicable - 0 or 1 and defining if it should be used or not in the user interface. Sometimes it is only used internally
input_type - radio or checkbox, radio-type  is used to store selectors of aggregation levels and if not specified it will be "checkboxes" by default
result_item["sum_item"][1..nn] - list of result_items aggregated by sum-function can be used for map thematic visualisation and for the diagram
result_item["avg_item"][1..nn] - list of result_items aggregated by avg-function
result_item["single_item"][1..nn] - list of result_items with aggregation,only display the actual values, can not be used for thematic visualisation nor for diagram
result_item["sort_item"][1..nn] - list of result_items for sorting the result list, diagram data etc

*/

function t_old($text)
{
	return $text;
}

$result_definition_item = array
(
	"sitename" =>
		array( 
		"table"=>"tbl_sites", 
		"column" => "tbl_sites.site_name", 
		"text" => "Site name",
		"result_type"=>"single_item",
		"activated" => true,
		"parents" => array("ROOT"),
		),
	"record_type" =>
		array( 
		"table"=>"tbl_record_types",
		"column" => "tbl_record_types.record_type_name",
		"text" => "Record type(s)",
		"result_type"=>"text_agg_item",
		"activated" => true,
		"parents" => array("ROOT"),

		),
	"analysis_entities" =>
		array(
		"table"=>"tbl_analysis_entities", 
		"column" => "tbl_analysis_entities.analysis_entity_id", 
		"text" => "Filtered records",
		"result_type"=>"single_item",
		"activated" => true,
		"parents" => array("ROOT"),
		),
    "site_link" =>
		array( 
		"table"=>"tbl_sites", 
		"column" => "tbl_sites.site_id", 
		"text" => "Full report",
		"result_type"=>"link_item",
                "link_url"=>"api/report/show_site_details.php?site_id",
                "link_label"=>"Show site report",
		"activated" => true,
		"parents" => array("ROOT"),
		
		),
    "site_link_filtered" =>
		array( 
		"table"=>"tbl_sites", 
		"column" => "tbl_sites.site_id", 
		"text" => "Filtered report",
		"result_type"=>"link_item",
                "link_url"=>"api/report/show_site_details.php?site_id",
                "link_label"=>"Show filtered report",
		"activated" => true,
		"parents" => array("ROOT"),
		),
    "aggregate_all_filtered" =>
		array( 
		"table"=>"tbl_aggregate_samples", 
		"column" => "'Aggregated'::text", 
		"text" => "Filtered report",
		"result_type"=>"link_item_filtered",
    "link_url"=>"api/report/show_details_all_levels.php?level",
		"activated" => true,
		"parents" => array("ROOT"),
		),
      "sample_group_link"	=>
	array(
		"table"=>"tbl_sample_groups",
		"column" => "tbl_sample_groups.sample_group_id",	
		"text" => "Full report"	,
		"result_type"=>"link_item",
		"link_url"=>"api/report/show_sample_group_details.php?sample_group_id",
		"activated" => true,
		"parents" => array("ROOT")),
        "sample_group_link_filtered"	=>
            array(
                "table"=>"tbl_sample_groups",
                "column" => "tbl_sample_groups.sample_group_id",	
                "text" => "Filtered report",
                "result_type"=>"link_item",
                "link_url"=>"api/report/show_sample_group_details.php?sample_group_id",
                "activated" => true,
                "parents" => array("ROOT")),
    "abundance" =>
		array(
		"table"=>"tbl_abundances", 
		"column" => " tbl_abundances.abundance", 
		"text" => "number of taxon_id",
		"result_type"=>"single_item",
		"activated" => true,
		"parents" => array("ROOT"),
		),
	"taxon_id" =>
		array(
		"table"=>"tbl_abundances", 
		"column" => " tbl_abundances.taxon_id", 
		"text" => "Taxon id  (specie)",
		"result_type"=>"single_item",
		"activated" => true,
		"parents" => array("ROOT"),
		),
    "dataset" =>
		array(
		"table"=>"tbl_datasets", 
		"column" => "tbl_datasets.dataset_name", 
		"text" => "Dataset",
		"result_type"=>"single_item",
		"activated" => true,
		"parents" => array("ROOT"),
		),
      "dataset_link" =>
		array(
		"table"=>"tbl_datasets", 
		"column" => "tbl_datasets.dataset_id", 
		"text" => "Dataset details",
		"result_type"=>"single_item",
               "link_url"=>"applications/sead/show_dataset_details.php?dataset_id",
		"activated" => true,
		"parents" => array("ROOT"),
		),
       "dataset_link_filtered" =>
		array(
		"table"=>"tbl_datasets", 
		"column" => "tbl_datasets.dataset_id", 
		"text" => "Filtered report",
		"result_type"=>"single_item",
               "link_url"=>"applications/sead/show_dataset_details.php?dataset_id",
		"activated" => true,
		"parents" => array("ROOT"),
		),
	"sample_group" =>
		array(
		"table"=>"tbl_sample_groups", 
		"column" => "tbl_sample_groups.sample_group_name", 
		"text" => "Sample group",
		"result_type"=>"single_item",
		"activated" => true,
		"parents" => array("ROOT"),
		),
	"methods" =>
		array(
		"table"=>"tbl_methods", 
		"column" => "tbl_methods.method_name", 
		"text" => "Method",
		"result_type"=>"single_item",
		"activated" => true,
		"parents" => array("ROOT"),
		),
);

?>