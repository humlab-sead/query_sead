<?php
/* 
 * This file is setting the specific server parameters for the application to be run into the framework. 
 * 
 */

$applicationPath = "applications/$applicationName";
if(!isset($facetDefinitionFile))
    $facetDefinitionFile = "$applicationPath/js_facets_def.php";
if(!isset($fbDefinitionFile))
    $fbDefinitionFile = "$applicationPath/fb_def.php";
if(!isset($layoutFile))
    $layoutFile = "$applicationPath/theme/layout.php";
if(!isset($applicationStyleSheet))
    $applicationStyleSheet = "$applicationPath/theme/style.css";
if(!isset($applicationTitle))
    $applicationTitle = "SEAD - The Strategic Environmental Archaeology Database";

//this is an English interface. do not touch the language settings.
// $language[]...
$max_result_display_rows = 10000;

// override the default english labels see applications/applicationSpecification.php
$language[RESULT_DIAGRAM_NAME] = "Diagram";
$language[RESULT_MAP_NAME] = "Map";
$language[SEARCH_FILTER_NAME] = "Filter";
$language[RESULT_LIST_NAME]="Table";
$language[CURRENT_FILTER] = "Selections";
?>
