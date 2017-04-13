<?php
/*
file: language.php
Get the phrase client and store into the database in the specified language
*/

require_once(__DIR__ . "/../config/environment.php");
require_once(__DIR__ . "/../config/bootstrap_application.php");
require_once(__DIR__ . "/../connection_helper.php");

function language_show_translation_panel()
{
}

function get_default_view_state()
{
    global $current_view_state_id;
    return $current_view_state_id;
}

function get_view_state_language($view_state_id)
{
    global $view_state_table, $default_language;

    $row = ConnectionHelper::queryRow("select * from $view_state_table where view_state_id = $view_state_id;");

    $view_state_xml = isset($row) ?  $row["view_state"] : "";

    $xml_object = new SimpleXMLElement($view_state_xml);
    $client_language = (string)$xml_object->client_language;

    if ($client_language == '') {
        if (isset($default_language)) {
            $client_language = $default_language;
        } else {
            $default_language = "se_SV";
        }
    }
    return $client_language;
}

function t_subs($text, $variables)
{
    $text = str_replace("__amp__", "&", $text);
    return $text;
}

/*
function: language_receive_new_phrase
get a none-existing phrase from clients and add that phrase to the database.
*/
function language_receive_new_phrase($xml)
{
    global $phrases_schema;
    $schema_str = !empty($phrases_schema) ? $phrases_schema . "." : "";
    $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    $phrase = pg_escape_string($data->original_phrase);
    $rs2 = ConnectionHelper::query("SELECT id FROM {$schema_str}original_phrases WHERE phrase='$phrase'");
    $exists = pg_num_rows($rs2);
    if ($exists == 0) {
        ConnectionHelper::query("INSERT INTO {$schema_str}original_phrases (phrase) VALUES ('$phrase')");
    }
    return "";
}

/*
function: language_create_javascript_languages_definition_array
Print the available languages as a javascript array for the client to use
*/
function language_create_javascript_languages_definition_array()
{
    global $phrases_schema;
    $schema_str = isset($phrases_schema) ? $phrases_schema . "." : "";
    $res = ConnectionHelper::query("SELECT * FROM {$schema_str}language_definitions");
    $js_out = "<script type=\"text/javascript\">\n";
    $js_out .= "var language_definitions = [];\n";
    while ($language_def = pg_fetch_assoc($res)) {
        $js_out .= "language_definitions[{$language_def['id']}] = [];\n";
        $js_out .= "language_definitions[{$language_def['id']}]['id'] = " . $language_def['id'] . ";\n";
        $js_out .= "language_definitions[{$language_def['id']}]['language'] = \"" . $language_def['language'] . "\";\n";
        $js_out .= "language_definitions[{$language_def['id']}]['language_name'] = \"" . $language_def['language_name'] . "\";\n";
    }
    $js_out .= "</script>\n";
    return $js_out;
}

/*
function: language_create_javascript_translation_arrays
Get the phrases from database in the specified language and store it a global list
Returns the phrases as javascript literal (one array).
*/
function language_create_javascript_translation_arrays()
{
    global $phrases_schema;
    $schema_str = isset($phrases_schema) ? $phrases_schema . "." : "";

    $js_out = "<script type=\"text/javascript\">\n";
    $js_out .= "var languages = [];\n";

    $q = "SELECT * FROM {$schema_str}language_definitions";;
    $rs = ConnectionHelper::query($q);
    while ($row = pg_fetch_assoc($rs)) {
        //creates arrays for available languages
        $js_out .= "languages['" . $row["language"] . "'] = [];\n";
    }

    // fetch all orginal phrase and translated phrases.
    // If there are none existing transalated then add empty strings for all aviables language. (That why language_defition are joined without join-condiition)

    $q = "SELECT *, language as def_language
          FROM  {$schema_str}original_phrases, {$schema_str}translated_phrases
          WHERE {$schema_str}translated_phrases.original_phrase_id= {$schema_str}original_phrases.id
            AND language is not null and language <> ''";

    $rs2 = ConnectionHelper::query($q);
    while ($row = pg_fetch_assoc($rs2)) {
        $phrase_key = str_replace("\"", "\\\"", $row["phrase"]);
        $phrase_key = str_replace("__amp__", "&", $phrase_key);
        if (!empty($row["language"])) {
            $js_out .= "languages[\"" . $row["language"] . "\"][\"" . $phrase_key . "\"] = \"" . $row["translated_phrase"] . "\";\n";
        } else {
            $js_out .= "languages[\"" . $row["def_language"] . "\"][\"" . $phrase_key . "\"] = \"" . $row["translated_phrase"] . "\";\n";
        }
    }
    $js_out .= "</script>";
    return $js_out;
}

if (isset($_GET['f'])) {
    switch ($_GET['f']) {
        case 1:
            //echo language_create_javascript_translation_arrays();
            break;
        case "language_new_phrase":
            echo language_receive_new_phrase($_POST['xml']);
            break;
    }
}

?>