<?php
/*
file: bootstrap_application.php (SEAD)
*/

require_once __DIR__ . "/facet_graph.php";

@ini_set('log_errors','On');
@ini_set('display_errors','Off');
@ini_set('error_log', __DIR__ . '/../../errors.log');

$current_view_state_id  = 7;
$language_table         = "metainformation.tbl_languages";
$phrases_schema         = "metainformation";
$language_id_column     = "language_id";
$view_state_table       = "metainformation.tbl_view_states";
$direct_count_table     = "tbl_analysis_entities";
$direct_count_column    = "tbl_analysis_entities.analysis_entity_id";
$indirect_count_table   = "tbl_dating_periods";
$indirect_count_column  = "tbl_dating_periods.dating_period_id";

include_once __DIR__ . "/result_definitions.php";
include_once __DIR__ . "/facet_definitions.php";

define('I',1000);


?>