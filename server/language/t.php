<?php

/*
function: t
future function for translating phrases
returns: text

*/
function t($text, $client_language, $variables = []) {
    
    global $phrases_schema;
    $schema_str = isset($phrases_schema) ? $phrases_schema."." : "";
    
    $keys = array_keys($variables);
    
    if($client_language == "sv_SE") {
        $text = str_replace($keys, $variables, $text);
        return $text;
    }
    
    $a = pg_query("SELECT id FROM ".$schema_str."original_phrases WHERE phrase='".pg_escape_string($text)."'");
    
    if(pg_num_rows($a) > 0) {
        $original_phrase = pg_fetch_assoc($a);
        $a = pg_query("SELECT * FROM ".$schema_str."translated_phrases WHERE language='".$client_language."' AND original_phrase_id=".$original_phrase['id']);
        
        if(pg_num_rows($a) > 0) {
            $translated = pg_fetch_assoc($a);
            $tr_phrase = str_replace($keys, $variables, $translated['translated_phrase']);
            $tr_phrase = str_replace("__amp__", "&", $tr_phrase);
            if ($tr_phrase!="")
                return $tr_phrase;
            else
                return str_replace($keys, $variables, $text);
        }
        else {
            $text = str_replace($keys, $variables, $text);
            return $text;
        }
        
    }
    else {
        //doesn't exist in original_phrases, so create it
        $phrase = str_replace("&", "__amp__", $text);
        $phrase = pg_escape_string($phrase);
        pg_query("INSERT INTO ".$schema_str."original_phrases (phrase) VALUES ('".$phrase."')");
        $text = str_replace($keys, $variables, $text);
        return $text;
    }
    
}

?>