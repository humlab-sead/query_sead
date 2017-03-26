<?php
/*
file: fb_def.php (SEAD)
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

require_once __DIR__."/credentials.php";
require_once __DIR__."/../../server/connection_helper.php";

@ini_set('log_errors','On');
@ini_set('display_errors','Off');
@ini_set('error_log', __FILE__ . '../../errors.log');

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

include_once(__DIR__."/facet_definitions.php");

$direct_count_table="tbl_analysis_entities";
$direct_count_column="tbl_analysis_entities.analysis_entity_id";
$indirect_count_table="tbl_dating_periods";
$indirect_count_column="tbl_dating_periods.dating_period_id";

// by defining the related the columns of to tables, the tables becomes linked.
// If you now how to related they become related.
// Defintion of the database model

$conn = ConnectionHelper::createConnection();

$q2 = "select * from metainformation.tbl_foreign_relations";

$rs2 = ConnectionHelper::query($conn, $q2);

while ($row = pg_fetch_assoc($rs2))
{
    $sourceTable = $row["source_table"];
    $sourceColumn = $row["source_column"];
    $targetTable = $row["target_table"];
    $targetColumn = $row["target_column"];
    $join_columns[$sourceTable][$targetTable] = array (
        "home_columns" => array ( "$sourceTable.\"$sourceColumn\""),
        "remote_columns" => array ( "$targetTable.\"$targetColumn\""),
        "join_condition" => " inner join $targetTable on ($sourceTable.\"$sourceColumn\"=$targetTable.\"$targetColumn\")\n",
        "weight" => $row["weight"]);
}
pg_close($conn);

//Defining the graph of tables
// Make all joins unidirectional, so it will be the same join condition whatever direction you go.
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

// make bidirectional
foreach ($join_columns as $key => $pair)
{
    foreach ($pair as $key2 => $element)
    {
        $join_columns[$key2][$key]=$element;
    }
}

foreach ($facet_definition as $facet_key =>$facet_definition_temp    )
{
    if (isset($facet_definition_temp["alias_table"]))
    {
        $temp_joins_conditions=$join_columns[$facet_definition_temp["table"]];
        $list_of_alias_tables[$facet_definition_temp["alias_table"]]=$facet_definition_temp["table"];
        foreach ($temp_joins_conditions as $remote_table =>$join_info_array)
        {
            $join_info_array["home_columns"]=  str_replace($facet_definition_temp["table"], $facet_definition_temp["alias_table"], $join_info_array["home_columns"]);
            $join_info_array["remote_columns"]=  str_replace($facet_definition_temp["table"], $facet_definition_temp["alias_table"], $join_info_array["remote_columns"]);
            $join_info_array["join_condition"]=  str_replace($facet_definition_temp["table"], $facet_definition_temp["alias_table"], $join_info_array["join_condition"]);
            $join_columns[$facet_definition_temp["alias_table"]][$remote_table]=$join_info_array; // copy the elements
            $join_columns[$remote_table][$facet_definition_temp["alias_table"]]=$join_info_array; // copy the elements
        }
    }
}

$f_tables = array();
$temp_graph=$join_columns;
$counter=0;
foreach ($join_columns as $key => $pair)
{
    foreach ($pair as $key2 => $element)
    {
        if(!array_key_exists($key,$f_tables)){
            $f_tables[$key] = $counter++;
        }
        if(!array_key_exists($key2,$f_tables)){
            $f_tables[$key2] = $counter++;
        }
    }
}

// Create the graph ie the edges betwween the nodes into the matrix
define('I',1000);
$points = Array();

foreach ($join_columns as $key => $pair)
{
    foreach ($pair as $key2 => $element)
    {
        $weight = $join_columns[$key][$key2]["weight"];
        $ourMap[$f_tables[$key]][$f_tables[$key2]] = $weight;
    }
}
?>