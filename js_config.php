<?php
/*
File: js_config.php
This files is assigning global parameters so the executing script have access to such data and functinoality
Specifically, the $applicationName and session key .

*/ 
require_once 'applications/applicationSpecification.php';
function get_js_server_address() {
	return $_SERVER['SERVER_NAME'];
}

/* 
Function: get_js_server_prefix_path
returns:
the path of the server
*/
function get_js_server_prefix_path() {
	$path = $_SERVER['PHP_SELF'];
	$pathinfo = pathinfo($path);
	return $pathinfo['dirname']."/";
}
/* 
Function: getApplication

returns:
applicationName
*/
function getApplication(){
    global $applicationName;
    return $applicationName;
}

/**
 *  General method for creating a session specific variable. This is
 *  a short workaround for the session_handling in php which limits the amount
 * of connections possible to a psotgresql database.
 *
 * The session variable is constructed from the client ip and a timestamp
 * at point of creation.
 *
 * @return string
 */
function generateSessionKey(){
    $ip = $_SERVER['REMOTE_ADDR'];
    $timeStamp = time();
    return sha1($ip . "_" . $timeStamp);
}

?>