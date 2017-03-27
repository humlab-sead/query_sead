<?php
/*
file: js_result_def.php
This file is generating a java-script output contain the result variable to be used in the client-interfaces.

I also load which result modules that are present.

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
// loads the javascript library for the result modules 
$modules = array();
$path = "client/result_modules";
$dir = scandir($path);
foreach($dir as $entry) {
    if($entry != "." && $entry != ".." && is_file($path."/".$entry) && substr($entry, -2, 2) == "js") {
        echo "<script type=\"text/javascript\" src=\"".$path."/".$entry."\"></script>\n";
        if(substr($entry, strlen($entry)-1, 1) != "~") { //don't load quanta backup-files (ending with tilde)
            $pos = strpos($entry, ".");
            $module_name_parts = str_split($entry, $pos);
            $modules[] = $module_name_parts[0];
        }
    }
}

echo "<script type=\"text/javascript\" >";
echo "var result_modules = Array();\n";
foreach($modules as $module) {
    echo "result_modules.push('".$module."');\n";
}

$out = "var result_variable_aggregation_types = Array();\n";

$i = 0;
foreach($result_definition as $key => $item) {
    
    if($result_definition[$key]['aggregation_selector'] === true) {
        $out .= "result_variable_aggregation_types['".$key."'] = Array();\n";
        $out .= "result_variable_aggregation_types['".$key."']['id'] = \"".$key."\";\n";
        $out .= "result_variable_aggregation_types['".$key."']['title'] = \"".$item['text']."\";\n";
        $out .= "result_variable_aggregation_types['".$key."']['activated'] = ".$item['activated'].";\n";
        $out .= "result_variable_aggregation_types['".$key."']['type'] = \"".$item['result_type']."\";\n";
        $out .= "result_variable_aggregation_types['".$key."']['aggregation_type'] = \"".$item['aggregation_type']."\";\n";
        $i++;
    }
}

echo $out;

$out = "var result_variable_definitions = Array();\n";
$i=0;
$default_slots_num = 0;
foreach ($result_definition as $result_key => $element)
{
    if($element["input_type"] == "radio") {
        $result_variable_aggregation_options []= $element;
    }
    if ($element["applicable"] == "1" && $element["input_type"] != "radio") {
        $out .= "result_variable_definitions[".$i."] = Array();\n";
        $out .= "result_variable_definitions[".$i."][\"id\"] = \"".$result_key."\";\n";
        $out .= "result_variable_definitions[".$i."][\"name\"] = \"".$element["text"]."\";\n";
        $out .= "result_variable_definitions[".$i."][\"dom_id\"] = \"result_variable_".$result_key."\";\n";
        $out .= "result_variable_definitions[".$i."][\"type\"] = \"".$element["result_type"]."\";\n";
        $default = $element['result_item']['sum_item'][0]['activated'] ? "true" : "false";
        $out .= "result_variable_definitions[".$i."][\"default\"] = ".$default.";\n";
        $js_parents = "[";
        $parents_ticker = 0;
        foreach($element['parents'] as $parent) {
            $js_parents .= "'".$parent."',";
            $parents_ticker++;
        }
        $js_parents = substr($js_parents, 0, strlen($js_parents)-1);
        $js_parents .="]";
        $out .= "result_variable_definitions[".$i."][\"parents\"] = ".$js_parents.";\n";
        $out .= "\n";
        $i++;
    }
}
$out .= "</script>";
echo $out;

?>