<?php
/*
file: get_result_definitions.php
This file is generating a java-script output contain the result variable to be used in the client-interfaces.

Information of the result_definition are defined in
* <bootstrap_application.php>

The array has this format:
id - id of the result_definition
name - title
input_type - radio or checkbox(default)
default - default or not (0 or 1)

*/
require_once(__DIR__ . "/../server/config/environment.php");
require_once(__DIR__ . "/../server/config/bootstrap_application.php");

global $result_definition;

// ROGER: NOT USED???
$out = "\nvar result_variable_aggregation_types = [];\n";
foreach($result_definition as $key => $item) {
    if($result_definition[$key]['aggregation_selector'] === true) {
        $out .= "result_variable_aggregation_types['$key'] = [];\n";
        $out .= "result_variable_aggregation_types['$key']['id'] = '$key';\n";
        $out .= "result_variable_aggregation_types['$key']['title'] = \"{$item['text']}\";\n";
        $out .= "result_variable_aggregation_types['$key']['activated'] = {$item['activated']};\n";
        $out .= "result_variable_aggregation_types['$key']['type'] = \"{$item['result_type']}\";\n";
        $out .= "result_variable_aggregation_types['$key']['aggregation_type'] = \"{$item['aggregation_type']}\";\n";
    }
}
echo $out;

$out = "var result_variable_definitions = [];\n";
$i=0;
foreach ($result_definition as $result_key => $element)
{
    if ($element["applicable"] == "1" && $element["input_type"] != "radio") {
        
        $default = $element['result_item']['sum_item'][0]['activated'] ? "true" : "false";
        $parents = array_map(function($x) { return "'$x'"; }, $element['parents']);

        $out .= "result_variable_definitions[$i] = [];\n";
        $out .= " result_variable_definitions[$i]['id'] = \"$result_key\";\n";
        $out .= " result_variable_definitions[$i]['name'] = \"{$element['text']}\";\n";
        $out .= " result_variable_definitions[$i]['dom_id'] = \"result_variable_$result_key\";\n";
        $out .= " result_variable_definitions[$i]['type'] = \"{$element['result_type']}\";\n";
        $out .= " result_variable_definitions[$i]['default'] = $default;\n";
        $out .= " result_variable_definitions[$i]['parents'] = [" . implode(", ", $parents) . "];\n\n";

        $i++;
    }
}
echo $out;
?>