<?php
/* 
 * Select which application the framework should run.
 * 
 */

$applicationName = "sead";
$application_name = "sead";

// definitions for output names used throughout the interface.
// localized versions can be implemented in each application's
// applicationInitialization.php. Just override the needed
// output names

// define('SEARCH_FILTER_NAME', 0);
// define('RESULT_MAP_NAME', 1);
// define('RESULT_DIAGRAM_NAME', 2);
// define('RESULT_LIST_NAME', 3);
// define('CONTACT_WORD', 4);
// define('FAQ_WORD', 5);
// define('LOGIN_WORD', 6);
// define('HELP_WORD', 7);
// define('SEARCH_TABLE_INFORMATION',8);
// define('SEARCH_TABLE_ANALYSIS_OUTPUT', 9);
// define('RESULT_TABLE_SAVE_LINK_NAME', 10);
// define('RESULT_TABLE_ANALYSIS_LINK_NAME', 11);
// define('CURRENT_FILTER', 12);

// if(!isset($language))
//     $language = array();

// $language[SEARCH_FILTER_NAME] = "Search filter";
// $language[RESULT_MAP_NAME] = "Map";
// $language[RESULT_DIAGRAM_NAME] = "Diagram";
// $language[RESULT_LIST_NAME] = "Table";
// $language[CONTACT_WORD] = "Contact";
// $language[FAQ_WORD] = "FAQ";
// $language[LOGIN_WORD] = "Login";
// $language[HELP_WORD] = "Help";
// $language[CURRENT_FILTER] = "Current filters";

$max_result_display_rows = 100;

$applicationPath = "applications/$applicationName";

$facetDefinitionFile = $facetDefinitionFile ?? "$applicationPath/js_facets_def.php";
$fbDefinitionFile = $fbDefinitionFile ?? "$applicationPath/fb_def.php";
$layoutFile = $layoutFile ?? "$applicationPath/layout.php";
$applicationStyleSheet = $applicationStyleSheet ?? "$applicationPath/theme/style.css";
$applicationTitle = $applicationTitle ?? "SEAD - The Strategic Environmental Archaeology Database";

$max_result_display_rows = 10000;

// override the default english labels see applications/applicationSpecification.php
// $language[RESULT_DIAGRAM_NAME] = "Diagram";
// $language[RESULT_MAP_NAME] = "Map";
// $language[SEARCH_FILTER_NAME] = "Filter";
// $language[RESULT_LIST_NAME]="Table";
// $language[CURRENT_FILTER] = "Selections";

?>
