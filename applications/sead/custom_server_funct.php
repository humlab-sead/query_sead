<?php
/*
file: custom_server_funct.php (SHiPS)
These are the function that are specific to SHIPS.
Some functions need to exist but are only returning "true", but specified different in other application
others such as list

see also:
- <custom_map_server_functions.php (SHIPS)>	 maps_functions
*/

require_once __DIR__."/custom_map_server_functions.php";

/*
function: get_save_query
this function uses the $conn but not be closed in the function.
it returns true if the function executes without problem.
*/
function get_save_query($query,$session_id,$conn){
    return true;
}

/*********************RESULT TABLE OUTPUT **************************/
/*
function: create_custom_result_table_header (SHIPS)
creates the head of the result list for each application
*/
function create_custom_result_table_header($rs, $facet_params, $result_params,$save_data_link_xls,$cache_id,$save_data_link_text){
    global $max_result_display_rows, $application_name;
    
    $str = htmlspecialchars($save_data_link);
    $tot_records=pg_numrows($rs);
    $tot_columns=0;
    foreach($result_params["items"] as $item)
    {
        $tot_columns++;
    }
    
    $use_xls=false;
    if (($tot_records*$tot_columns)<10000)
    {
        $use_xls=true;
    }
    
    if (pg_num_rows($rs)> $max_result_display_rows)
    {
        $phrase=  t("Din sökning resulterade i !number_of_rows träffar. De första !max_result_display_rows träffarna visas nedan ",$facet_params["client_language"],array ("!number_of_rows"=> pg_num_rows($rs),"!max_result_display_rows"=> $max_result_display_rows ))   ;
    }
    else
    {
        $phrase=  t("Din sökning resulterade i !number_of_rows träffar. ",$facet_params["client_language"],array ("!number_of_rows"=> pg_num_rows($rs))) ;
    }
    
    $phrase.=" <a href=\"$save_data_link_text\" id=\"download_link\" >".t("Spara all data till text-fil.",$facet_params["client_language"])."</a>";
    if ($use_xls)
    {
        $phrase.="   <a href=\"$save_data_link_xls\" id=\"download_link2\" >".t("Spara all data till kalkylblad.",$facet_params["client_language"])."</a>";
    }
    return	$phrase;
}

function render_java_script_arguments($conn,$obj_id)
{
    return "";
}

function render_java_script_from_id($conn,$obj_id)
{
    return $java_script;
}

?>