<?php
/*
file: get_result_definitions.php
This file is generating a java-script output contain the result variable to be used in the client-interfaces.
*/
require_once(__DIR__ . "/../server/config/environment.php");
require_once(__DIR__ . "/../server/config/bootstrap_application.php");

$out = "\nvar result_variable_aggregation_types = [];\n";
foreach(ResultDefinitionRegistry::getDefinitions() as $key => $item) {
    if($item->aggregation_selector !== true) {
        continue;
    }
    $out .= "result_variable_aggregation_types['$key'] = [];\n";
    $out .= "result_variable_aggregation_types['$key']['id'] = '$key';\n";
    $out .= "result_variable_aggregation_types['$key']['title'] = \"{$item->text}\";\n";
    $out .= "result_variable_aggregation_types['$key']['activated'] = {$item->activated};\n";
    $out .= "result_variable_aggregation_types['$key']['type'] = \"{$item->result_type}\";\n";
    $out .= "result_variable_aggregation_types['$key']['aggregation_type'] = \"{$item->aggregation_type}\";\n";
}
echo $out;

$out = "var result_variable_definitions = [];\n";
$i=0;
foreach (ResultDefinitionRegistry::getDefinitions() as $key => $item)
{
    if (!($element["applicable"] == "1" && $element["input_type"] != "radio")) {
        continue;
    }

    $default = $item->fields['sum_item'][0]->activated ? "true" : "false";

    $parents = array_map(function($x) { return "'$x'"; }, $item->parents);

    $out .= "result_variable_definitions[$i] = [];\n";
    $out .= "result_variable_definitions[$i]['id'] = \"$key\";\n";
    $out .= "result_variable_definitions[$i]['name'] = \"{$item->text}\";\n";
    $out .= "result_variable_definitions[$i]['dom_id'] = \"result_variable_$key\";\n";
    $out .= "result_variable_definitions[$i]['type'] = \"{$item->result_type}\";\n";
    $out .= "result_variable_definitions[$i]['default'] = $default;\n";
    $out .= "result_variable_definitions[$i]['parents'] = [" . implode(", ", $parents) . "];\n\n";

    $i++;
}
echo $out;
?>