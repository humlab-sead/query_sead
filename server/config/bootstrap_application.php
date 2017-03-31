<?php
/*
file: bootstrap_application.php (SEAD)
This file is setting the specific server-side parameters for the application to run into the framework.
o The defintion of the counting parameters
o The database model as a graph reprented by edges and weights
o Facet definitions in <facet_definitions.php (SEAD)>
o Result definition in <result_definitions.php (SEAD)>

Definition how to join tables ($join_columns):
This is essentially a graph where the nodes are the tables and the edges are the join-condition.

It is stored as an associtative matrix (a edge-list) called join_columns where the keys are the original table name (from_table) and the target table name (to_table).
The keys can be seen as the nodes in the graph and edges are holding the join-condition as the following

key home_column = the column name in the source table identified as the  left hand side in the sql join
key remote_column = the column name in the target table  identified as the right hand side in the sql join.

For example, table a has a relationship
with table b via a column called b_id. This column points via a foreign key to the identifier id  of table b. Thus the array saved at edge ['a']['b'] in join_columns will look like the following
array(home_column = 'b_id',remote_column = 'a.id')

This will later on be concatenated into the join clause (usually theta joins)
a.b_id = b.id

*/

require_once __DIR__ . "/credentials.php";
require_once __DIR__ . "/../connection_helper.php";

@ini_set('log_errors','On');
@ini_set('display_errors','Off');
@ini_set('error_log', __DIR__ . '/../../errors.log');

$current_view_state_id = 7;

$cache_seq="metainformation.file_name_data_download_seq"; // which seq to use for find id for filenames to download

define('CONNECTION_STRING',
    "host=" . $db_information['db_host'] . " " .
    "user=" . $db_information['db_user'] . " " .
    "dbname=" . $db_information['db_database'] . " " .
    "password=" . $db_information['db_password'] . " " .
    "port=" . $db_information['db_port']);

$language_table="metainformation.tbl_languages";
$phrases_schema="metainformation";
$language_id_column="language_id";
$view_state_table="metainformation.tbl_view_states";

// Global variable for keeping track of requests. Is used in the client. Is sent from the client with the post and is just echoed back.
$request_id = "";  // ROGER Not used??

$filter_by_text=true;

include_once("result_definitions.php"); // this file holds all result_defintion items

$result_definition["site_level"]["text"]="Site level";
$result_definition["site_level"]["applicable"]="0";
$result_definition["site_level"]['activated'] = "true";
$result_definition["site_level"]["result_type"]="single_item";
$result_definition["site_level"]["aggregation_type"]="site_level";
$result_definition["site_level"]["input_type"]="checkboxes";
$result_definition["site_level"]['aggregation_selector'] = true;
$result_definition["site_level"]["result_item"]["single_item"][]=$result_definition_item["sitename"] ;
$result_definition["site_level"]["result_item"]["sort_item"][]=$result_definition_item["sitename"] ;
$result_definition["site_level"]["result_item"]["text_agg_item"][]=$result_definition_item["record_type"];
$result_definition["site_level"]["result_item"]["count_item"][]=$result_definition_item["analysis_entities"];
$result_definition["site_level"]["result_item"]["link_item"][]=$result_definition_item["site_link"];
$result_definition["site_level"]["result_item"]["link_item_filtered"][]=$result_definition_item["site_link_filtered"];

// $result_definition["site_level"] = [
//     "text"                   => "Site level",
//     "applicable"             => "0",
//     'activated'              => "true",
//     "result_type"            => "single_item",
//     "aggregation_type"       => "site_level",
//     "input_type"             => "checkboxes",
//     'aggregation_selector'   => true,
//     "result_item" => [
//         "single_item"        => [ $result_definition_item["sitename"] ],
//         "sort_item"          => [ $result_definition_item["sitename"] ],
//         "text_agg_item"      => [ $result_definition_item["record_type"] ],
//         "count_item"         => [ $result_definition_item["analysis_entities"] ],
//         "link_item"          => [ $result_definition_item["site_link"] ],
//         "link_item_filtered" => [ $result_definition_item["site_link_filtered"] ]
//     ]
// ];

// $result_definition["sample_group_level"] = [
//     "text"                   => "Sample group level",
//     "applicable"             => "0",
//     'activated'              => "true",
//     "result_type"            => "single_item",
//     "aggregation_type"       => "sample_group_level",
//     "input_type"             => "checkboxes",
//     'aggregation_selector'   => true,
//     "result_item" => [
//         "single_item"        => [ $result_definition_item["sitename"], $result_definition_item["sample_group"] , $result_definition_item["record_type"]],
//         "sort_item"          => [ $result_definition_item["sitename"], $result_definition_item["sample_group"] ],
//         "count_item"         => [ $result_definition_item["analysis_entities"] ],
//         "link_item"          => [ $result_definition_item["sample_group_link"] ],
//         "link_item_filtered" => [ $result_definition_item["sample_group_link_filtered"] ]
//     ]
// ];

// $result_definition["aggregate_all"] =[
//     "text"                      => "Aggregate all",
//     "applicable"                => "0",
//     'activated'                 => "true",
//     "result_type"               => "single_item",
//     "aggregation_type"          => "aggregate_all",
//     "input_type"                => "checkboxes",
//     'aggregation_selector'      => true,
//     "result_item"               => [
//         "link_item_filtered"    => [ $result_definition_item["aggregate_all_filtered"] ],
//         "count_item"            => [ $result_definition_item["analysis_entities"] ]
//     ]
// ];


$result_definition["sample_group_level"]["text"]="Sample group level";
$result_definition["sample_group_level"]["applicable"]="0";
$result_definition["sample_group_level"]['activated'] = "true";
$result_definition["sample_group_level"]["result_type"]="single_item";
$result_definition["sample_group_level"]["aggregation_type"]="sample_group_level";
$result_definition["sample_group_level"]["input_type"]="checkboxes";
$result_definition["sample_group_level"]['aggregation_selector'] = true;
$result_definition["sample_group_level"]["result_item"]["single_item"][]=$result_definition_item["sitename"] ;
$result_definition["sample_group_level"]["result_item"]["single_item"][]=$result_definition_item["sample_group"] ;
$result_definition["sample_group_level"]["result_item"]["sort_item"][]=$result_definition_item["sitename"] ;
$result_definition["sample_group_level"]["result_item"]["sort_item"][]=$result_definition_item["sample_group"] ;
$result_definition["sample_group_level"]["result_item"]["single_item"][]=$result_definition_item["record_type"];
$result_definition["sample_group_level"]["result_item"]["count_item"][]=$result_definition_item["analysis_entities"];
$result_definition["sample_group_level"]["result_item"]["link_item"][]=$result_definition_item["sample_group_link"] ;
$result_definition["sample_group_level"]["result_item"]["link_item_filtered"][]=$result_definition_item["sample_group_link_filtered"] ;

$result_definition["aggregate_all"]["text"]="Aggregate all";
$result_definition["aggregate_all"]["applicable"]="0";
$result_definition["aggregate_all"]['activated'] = "true";
$result_definition["aggregate_all"]["result_type"]="single_item";
$result_definition["aggregate_all"]["aggregation_type"]="aggregate_all";
$result_definition["aggregate_all"]["input_type"]="checkboxes";
$result_definition["aggregate_all"]['aggregation_selector'] = true;
$result_definition["aggregate_all"]["result_item"]["link_item_filtered"][]=$result_definition_item["aggregate_all_filtered"] ;
$result_definition["aggregate_all"]["result_item"]["count_item"][]=$result_definition_item["analysis_entities"];

include_once(__DIR__ . "/facet_definitions.php");

$direct_count_table="tbl_analysis_entities";
$direct_count_column="tbl_analysis_entities.analysis_entity_id";
$indirect_count_table="tbl_dating_periods";
$indirect_count_column="tbl_dating_periods.dating_period_id";

class WeightedGraph {

    // Defines the relations between table columns i.e. the DB model

    public $joinColumns;
    public $tableIds;
    public $edges;
    public $aliasTables;

    private function createJoinElement($sourceTable, $sourceColumn, $targetTable, $targetColumn, $weight)
    {
        return [
            "home_columns"      => [ "$sourceTable.\"$sourceColumn\"" ],
            "remote_columns"    => [ "$targetTable.\"$targetColumn\"" ],
            "join_condition"    => " inner join $targetTable on ($sourceTable.\"$sourceColumn\"=$targetTable.\"$targetColumn\")\n",
            "weight"            => $weight
        ];
    }

    public function getJoinColumns()
    {
        global $facet_definition;
        // Define the table graph

        // Make all joins unidirectional i.e. same join condition no matter direction
        // Add the keys unique array of tables, where the keys are the table names
        // Representing the graph as a matrix
        // also creation of help variables for looking up tables names against number and vice versa

        /*
        * This will construct an associtative matrix called join_columns.
        * where the keys are the original table name (from_table) and the target table name
        * (to_table). The value stored in each position is an array describing the relation to travers these
        * two tables: key home_column = the column name in the source table identified as the
        * left hand side in the sql join; key remote_column = the column name in the target table
        * identified as the right hand side in the sql join. For example, table a has a relationship
        * with table b via a column called b_id. This column points via a foreign key to the identifier id
        * of table b. Thus the array saved at position ['a']['b'] in join_columns will look like the following
        * array(
        *      home_column = 'b_id',
        *      remote_column = 'id')
        *
        * This will later on be concatenated into the join clause (usually theta joins):
        *      a.b_id = b.id
        */

        $conn = ConnectionHelper::createConnection();

        $rs2 = ConnectionHelper::query($conn, "select * from metainformation.tbl_foreign_relations");

        while ($row = pg_fetch_assoc($rs2))
        {
            $sourceTable = $row["source_table"];
            $targetTable = $row["target_table"];
            $sourceColumn = $row["source_column"];
            $targetColumn = $row["target_column"];
            $weight = $row["weight"];
            $join_columns[$sourceTable][$targetTable] = $this->createJoinElement($sourceTable, $sourceColumn, $targetTable, $targetColumn, $weight);
            $join_columns[$targetTable][$sourceTable] = $this->createJoinElement($targetTable, $targetColumn, $sourceTable, $sourceColumn, $weight);
        }

        pg_close($conn);

        foreach ($facet_definition as $facet_key => $facet_definition_temp)
        {
            $alias_table = $facet_definition_temp["alias_table"];
            $source_table = $facet_definition_temp["table"];
            if (isset($alias_table))
            {
                $temp_joins_conditions = $join_columns[$source_table];
                foreach ($temp_joins_conditions as $remote_table => $join_info_array)
                {
                    $join_info_array["home_columns"]   = str_replace($source_table, $alias_table, $join_info_array["home_columns"]);
                    $join_info_array["remote_columns"] = str_replace($source_table, $alias_table, $join_info_array["remote_columns"]);
                    $join_info_array["join_condition"] = str_replace($source_table, $alias_table, $join_info_array["join_condition"]);

                    $join_columns[$alias_table][$remote_table] = $join_info_array;
                    $join_columns[$remote_table][$alias_table] = $join_info_array;
                }
            }
        }
        return $join_columns;
    }

    public function getAliasTables() {
        global $facet_definition;
        $tables = [];
        foreach ($facet_definition as $facet_key => $item)
        {
            if (isset($item["alias_table"]))
                $tables[$item["alias_table"]] = $item["table"];
        }
        return $tables;
    }

    public function generateTableIds($join_columns) {
        return array_flip(array_unique(array_keys($join_columns)));
    }

    public function createGraph($join_columns, $tableIds)
    {
        // Create the graph ie the edges betwween the nodes into the matrix
        foreach ($join_columns as $key => $pair)
        {
            foreach ($pair as $key2 => $element)
            {
                $weight = $join_columns[$key][$key2]["weight"];
                $edges[$tableIds[$key]][$tableIds[$key2]] = intval($weight);
            }
        }
        return $edges;
    }

    public function setup()
    {
        $this->joinColumns = $this->getJoinColumns();
        $this->tableIds = $this->generateTableIds($this->joinColumns);
        $this->edges = $this->createGraph($this->joinColumns, $this->tableIds);
        $this->aliasTables = $this->getAliasTables();
    }
}

$weightedGraph = new WeightedGraph();
$weightedGraph->setup();

$join_columns = $weightedGraph->joinColumns;
$ourMap = $weightedGraph->edges;

define('I',1000);

?>