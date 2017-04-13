<?php
/* 
file: result_definitions.php (SEAD)
o The definitions of the groups of result varibles and result variables

definition of result variables:
Has in the code the name "result_definition_item"

table - which table in the database to be used
column - which column to be used, should include the table name
text - label of the field

Definition of result composite item:
can have one or more result variables/result_items.

text - display name of the result defintion for the result control bar
applicable - 0 or 1 and defining if it should be used or not in the user interface. Sometimes it is only used internally
input_type - radio or checkbox, radio-type  is used to store selectors of aggregation levels and if not specified it will be "checkboxes" by default
fields->sum_item [1..nn] - list of result_items aggregated by sum-function can be used for map thematic visualisation and for the diagram
fields->avg_item[1..nn] - list of result_items aggregated by avg-function
fields->single_item[1..nn] - list of result_items with aggregation,only display the actual values, can not be used for thematic visualisation nor for diagram
fields->sort_item[1..nn] - list of result_items for sorting the result list, diagram data etc

*/

// TODO Move to database

$result_fields = array
(
    "sitename" =>
        array(
            "key" => "sitename",
            "table" => "tbl_sites",
            "column" => "tbl_sites.site_name",
            "text" => "Site name",
            "result_type" => "single_item",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
    "record_type" =>
        array(
            "key" => "record_type",
            "table" => "tbl_record_types",
            "column" => "tbl_record_types.record_type_name",
            "text" => "Record type(s)",
            "result_type" => "text_agg_item",
            "activated" => true,
            "parents" => array("ROOT"),

        ),
    "analysis_entities" =>
        array(
            "key" => "analysis_entities",
            "table" => "tbl_analysis_entities",
            "column" => "tbl_analysis_entities.analysis_entity_id",
            "text" => "Filtered records",
            "result_type" => "single_item",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
    "site_link" =>
        array(
            "key" => "site_link",
            "table" => "tbl_sites",
            "column" => "tbl_sites.site_id",
            "text" => "Full report",
            "result_type" => "link_item",
            "link_url" => "api/report/show_site_details.php?site_id",
            "link_label" => "Show site report",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
    "site_link_filtered" =>
        array(
            "key" => "site_link_filtered",
            "table" => "tbl_sites",
            "column" => "tbl_sites.site_id",
            "text" => "Filtered report",
            "result_type" => "link_item",
            "link_url" => "api/report/show_site_details.php?site_id",
            "link_label" => "Show filtered report",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
    "aggregate_all_filtered" =>
        array(
            "key" => "aggregate_all_filtered",
            "table" => "tbl_aggregate_samples",
            "column" => "'Aggregated'::text",
            "text" => "Filtered report",
            "result_type" => "link_item_filtered",
            "link_url" => "api/report/show_details_all_levels.php?level",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
    "sample_group_link" =>
        array(
            "key" => "sample_group_link",
            "table" => "tbl_sample_groups",
            "column" => "tbl_sample_groups.sample_group_id",
            "text" => "Full report",
            "result_type" => "link_item",
            "link_url" => "api/report/show_sample_group_details.php?sample_group_id",
            "activated" => true,
            "parents" => array("ROOT")),
    "sample_group_link_filtered" =>
        array(
            "key" => "sample_group_link_filtered",
            "table" => "tbl_sample_groups",
            "column" => "tbl_sample_groups.sample_group_id",
            "text" => "Filtered report",
            "result_type" => "link_item",
            "link_url" => "api/report/show_sample_group_details.php?sample_group_id",
            "activated" => true,
            "parents" => array("ROOT")),
    "abundance" =>
        array(
            "key" => "abundance",
            "table" => "tbl_abundances",
            "column" => " tbl_abundances.abundance",
            "text" => "number of taxon_id",
            "result_type" => "single_item",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
    "taxon_id" =>
        array(
            "key" => "taxon_id",
            "table" => "tbl_abundances",
            "column" => " tbl_abundances.taxon_id",
            "text" => "Taxon id  (specie)",
            "result_type" => "single_item",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
    "dataset" =>
        array(
            "key" => "dataset",
            "table" => "tbl_datasets",
            "column" => "tbl_datasets.dataset_name",
            "text" => "Dataset",
            "result_type" => "single_item",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
    "dataset_link" =>
        array(
            "key" => "dataset_link",
            "table" => "tbl_datasets",
            "column" => "tbl_datasets.dataset_id",
            "text" => "Dataset details",
            "result_type" => "single_item",
            "link_url" => "client/show_dataset_details.php?dataset_id",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
    "dataset_link_filtered" =>
        array(
            "key" => "dataset_link_filtered",
            "table" => "tbl_datasets",
            "column" => "tbl_datasets.dataset_id",
            "text" => "Filtered report",
            "result_type" => "single_item",
            "link_url" => "client/show_dataset_details.php?dataset_id",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
    "sample_group" =>
        array(
            "key" => "sample_group",
            "table" => "tbl_sample_groups",
            "column" => "tbl_sample_groups.sample_group_name",
            "text" => "Sample group",
            "result_type" => "single_item",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
    "methods" =>
        array(
            "key" => "methods",
            "table" => "tbl_methods",
            "column" => "tbl_methods.method_name",
            "text" => "Method",
            "result_type" => "single_item",
            "activated" => true,
            "parents" => array("ROOT"),
        ),
);

$result_definitions = [

    "site_level" => [
        "key" => "site_level",
        "text" => "Site level",
        "applicable" => "0",
        'activated' => "true",
        "result_type" => "single_item",
        "aggregation_type" => "site_level",
        "input_type" => "checkboxes",
        'aggregation_selector' => true,
        "fields" => [
            "single_item"           => [ "sitename" ],
            "text_agg_item"         => [ "record_type" ],
            "count_item"            => [ "analysis_entities" ],
            "link_item"             => [ "site_link" ],
            "sort_item"             => [ "sitename" ],
            "link_item_filtered"    => [ "site_link_filtered" ]
        ]
    ],
    "aggregate_all" => [
        "key" => "aggregate_all",
        "text" => "Aggregate all",
        "applicable" => "0",
        'activated' => "true",
        "result_type" => "single_item",
        "aggregation_type" => "aggregate_all",
        "input_type" => "checkboxes",
        'aggregation_selector' => true,
        "fields" => [
            "link_item_filtered"    => [ "aggregate_all_filtered" ],
            "count_item"            => [ "analysis_entities" ]
        ]
    ],
    "sample_group_level" => [
        "key" => "sample_group_level",
        "text" => "Sample group level",
        "applicable" => "0",
        'activated' => "true",
        "result_type" => "single_item",
        "aggregation_type" => "sample_group_level",
        "input_type" => "checkboxes",
        'aggregation_selector' => true,
        "fields" => [
            "single_item"           => [ "sitename", "sample_group", "record_type" ],
            "sort_item"             => [ "sitename", "sample_group"],
            "count_item"            => [ "analysis_entities" ],
            "link_item"             => [ "sample_group_link" ],
            "link_item_filtered"    => [ "sample_group_link_filtered" ]
        ]
    ]

];

class ResultField
{
    public $result_code = null;
    public $activated = "true";
    public $column = null;
    public $link_label = null;
    public $link_url = null;
    public $result_type = "";
    public $table = null;
    public $text = null;
    public $parents = [ "ROOT" ];

    function __construct($result_code, $property_array)
    {
        $this->result_code = $result_code;
        foreach ($property_array as $property => $value) {
            $this->$property = $value;
        }
    }

    public function isOfType($type)
    {
        return $this->result_type == $type;
    }
}

class ResultDefinition {
    
    public $key;
    public $text;
    public $activated = "true";
    public $aggregation_type;
    public $aggregation_selector;
    public $fields = [];
    public $input_type; 
    public $applicable = "0";
    public $result_type;

    function __construct($key, $property_array)
    {
        $this->key = $key;
        foreach ($property_array as $property => $value) {
            if (is_array($value)) {
                foreach ($value as $field_type => $field_keys)
                    foreach ($field_keys as $field_key)
                        $this->fields[$field_type][] = ResultFieldRegistry::getField($field_key);
            } else {
                $this->$property = $value;
            }
        }
    }
}

class ResultFieldRegistry
{
    public static $fields = null;

    public static function getFields()
    {
        global $result_fields;
        if (self::$fields == null) {
            self::$fields = [];
            foreach ($result_fields as $key => $properties) {
                self::$fields[$key] = new ResultField($key, $properties);
            }
        }
        return self::$fields;
    }

    public static function getField($key)
    {
        return self::getFields()[$key];
    }

}

class ResultDefinitionRegistry
{
    public static $definitions = null;

    public static function getDefinitions()
    {
        global $result_definitions;
        if (self::$definitions == null) {
            self::$definitions = [];
            foreach ($result_definitions as $key => $properties) {
                self::$definitions[$key] = new ResultDefinition($key, $properties);
            }
        }
        return self::$definitions;
    }

    public static function getDefinition($key)
    {
        return self::getDefinitions()[$key];
    }
}

