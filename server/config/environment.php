<?php
/* 
 * Select which application the framework should run.
 * 
 */

$applicationName = "sead";
$application_name = "sead";

$applicationTitle = $applicationTitle ?? "SEAD - The Strategic Environmental Archaeology Database";

function getServerName() {
	return $_SERVER['SERVER_NAME'];
}

function getServerPrefixPath() {
	$path = $_SERVER['PHP_SELF'];
	$pathinfo = pathinfo($path);
	return $pathinfo['dirname']."/";
}

function getApplication(){
    global $applicationName;
    return $applicationName;
}

class ConfigRegistry
{
    public const max_result_display_rows = 10000;
    public const application_name = "sead";
    public const cache_seq = "metainformation.file_name_data_download_seq";
    public const filter_by_text = true;

    public static function getMaxResultDefaultRows()
    {
        return self::max_result_display_rows;
    }

    public static function getApplicationName(){
        return self::application_name;
    }

    public static function getCacheSeq(){
        return self::cache_seq;
    }

    public static function getFilterByText(){
        return self::filter_by_text;
    }
}


/**
 * Creates a session specific key. This is a  workaround for the session_handling in php which limits the number
 * of concurrent open connections to a postgresql database.
 * The session variable is computed from client ip and a timestamp at point of creation.
 */
function generateSessionKey(){
    $ip = $_SERVER['REMOTE_ADDR'];
    $timeStamp = time();
    return sha1($ip . "_" . $timeStamp);
}

$client_result_module_path = "client/result_modules";

function getClientResultModules()
{
    global $client_result_module_path;
    return glob("$client_result_module_path/*.js");
}


?>
