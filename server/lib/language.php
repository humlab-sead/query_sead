<?php
/*
file: language.php
Get the phrase client and store into the database in the specified language
*/

require_once("applications/applicationSpecification.php");
require_once("applications/sead/fb_def.php");

function language_show_translation_panel() {
}

function get_default_view_state()
{
    global $current_view_state_id;
    return $current_view_state_id;;
}

function get_view_state_language($view_state_id)
{
    global $view_state_table, $default_language;
    
    if (!($conn = pg_connect(CONNECTION_STRING))) { echo "Error: pg_connect failed.\n"; exit; }
    
    $rs = pg_query($conn, "select * from $view_state_table where view_state_id=".$view_state_id.";");
    
    while ($row = pg_fetch_assoc($rs))
    {
        $view_state_xml=$row["view_state"];
    }
    
    $xml_object = new SimpleXMLElement($view_state_xml);
    $client_language=(string) $xml_object->client_language;
    
    if ($client_language=='')
    {
        if (isset($default_language))
        {
            $client_language=$default_language;
        }
        else
        {
            $default_language="se_SV";
        }
    }
    return $client_language;
}

function t_subs($text, $variables) {
    $text = str_replace($keys, $variables, $text);
    $text = str_replace("__amp__", "&", $text);
    return $text;
}

/*
function: language_receive_new_phrase
get a none-existing phrase from clients and add that phrase to the database.
*/
function language_receive_new_phrase($xml) {
    global $language_table;
    global $phrases_schema;
    $schema_str = isset($phrases_schema) ? $phrases_schema."." : "";
    $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    
    if (!($conn = pg_connect(CONNECTION_STRING))) { echo "Error 23: pg_connect failed.\n"; exit; }
    
    $phrase = pg_escape_string($data->original_phrase);
    
    //check that it really doesn't exist before doing anything
    $exist_str="SELECT id FROM ".$schema_str."original_phrases WHERE phrase='".$phrase."'";
    
    if (($rs2 = pg_query($conn, $exist_str)) <= 0) { echo "Error: cannot execute query3. $exist_str \n"; exit; }
    
    $exists= pg_num_rows($rs2 );
    
    if($exists == 0) {
        $exists_log = "valid";
        $insert_query="INSERT INTO ".$schema_str."original_phrases (phrase) VALUES ('".$phrase."')";
        if (($rs2 = pg_query($conn, $insert_query)) <= 0) { echo "Error: cannot execute query3. $insert_query \n"; exit; }
    } else {
        $exists_log = "dupe";
    }
}

/*
function: language_create_javascript_languages_definition_array
Print the available languages as a javascript array for the client to use
*/
function language_create_javascript_languages_definition_array() {
    
    if (!($conn = pg_connect(CONNECTION_STRING))) { echo "Error: pg_connect failed.\n"; exit; }
    global $phrases_schema;
    
    $schema_str = isset($phrases_schema) ? $phrases_schema."." : "";
    
    $res = pg_query("SELECT * FROM ".$schema_str."language_definitions");
    
    $js_out = "<script type=\"text/javascript\">\n";
    $js_out .= "var language_definitions = [];\n";
    while($language_def = pg_fetch_assoc($res)) {
        $js_out .= "language_definitions[".$language_def['id']."] = [];\n";
        $js_out .= "language_definitions[".$language_def['id']."]['id'] = ".$language_def['id'].";\n";
        $js_out .= "language_definitions[".$language_def['id']."]['language'] = \"".$language_def['language']."\";\n";
        $js_out .= "language_definitions[".$language_def['id']."]['language_name'] = \"".$language_def['language_name']."\";\n";
    }
    $js_out .= "</script>\n";
    
    return $js_out;
}

/*
function: language_create_javascript_translation_arrays
Get the phrases from database in the specified language and store it a global list
Returns the phrases as javascript literal (one array).
*/
function language_create_javascript_translation_arrays() {
    global $language_table;
    if (!($conn = pg_connect(CONNECTION_STRING))) { echo "Error: pg_connect failed.\n"; exit; }
    
    global $phrases_schema;
    $schema_str = isset($phrases_schema) ? $phrases_schema."." : "";
    
    $js_out = "<script type=\"text/javascript\">\n";
    $js_out .= "var languages = [];\n";
    
    $q="SELECT * FROM ".$schema_str."language_definitions";;
    if (($rs = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query3. $q \n"; exit; }
    while($row= pg_fetch_assoc( $rs))
    {
        //creates arrays for available languages
        $js_out .= "languages['".$row["language"]."'] = [];\n";
    }
    
    // fetch all orginal phrase and translated phrases.
    // If there are none existing transalated then add empty strings for all aviables language. (That why language_defition are joined without join-condiition)
    
    $q="SELECT *, language as def_language FROM ".$schema_str."original_phrases,".$schema_str."translated_phrases
    where ".$schema_str."translated_phrases.original_phrase_id=".$schema_str."original_phrases.id and language is not null and language <> ''";
    
    if (($rs2 = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query3. $q \n"; exit; }
    while($row= pg_fetch_assoc( $rs2))
    {
        $phrase_key = str_replace("\"", "\\\"", $row["phrase"]);
        $translated_phrase=str_replace('"', '\"', $row["translated_phrase"]);
        $phrase_key = str_replace("__amp__", "&", $phrase_key);
        if (!empty($row["language"]))
        {
            $js_out .= "languages[\"".$row["language"]."\"][\"".$phrase_key."\"] = \"".$row["translated_phrase"]."\";\n";
        }
        else
        {
            $js_out .= "languages[\"".$row["def_language"]."\"][\"".$phrase_key."\"] = \"".$row["translated_phrase"]."\";\n";
        }
    }
    $js_out .= "</script>";
    return $js_out;
}

function language_translation_form_submit($form_data) {
    
    foreach($form_data as $key => $value) {
        if(!empty($value)) {
            //pg_query("SELECT id FROM languages WHERE id=");
        }
    }
    return "Saved";
}

// function language_perform_update() {
//     /* REMOVED (initilize of DB) */
//     return "Updated";
// }


if (isset($_GET['f']))
{
    switch($_GET['f']) {
        case 1:
            //echo language_create_javascript_translation_arrays();
            break;
        case "language_new_phrase":
            echo language_receive_new_phrase($_POST['xml']);
            
            break;
        case "language_translation_form":
            if($_GET['password'] == "20ships13") {
                echo language_translation_form("en_GB");
            }
            else {
                exit;
            }
            break;
        case "language_translation_form_submit":
            echo language_translation_form_submit($_POST);
            break;
    }
}

?>