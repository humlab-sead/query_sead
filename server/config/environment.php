<?php
/* 
 * Select which application the framework should run.
 * 
 */

$applicationName = "sead";
$application_name = "sead";

$applicationTitle = $applicationTitle ?? "SEAD - The Strategic Environmental Archaeology Database";

$max_result_display_rows = 10000;

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

?>
