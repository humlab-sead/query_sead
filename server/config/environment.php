<?php

require_once __DIR__ . "/credentials.php";

define('CONNECTION_STRING',
    "host={$db_information['db_host']} " .
    "user={$db_information['db_user']} " .
    "dbname={$db_information['db_database']} " .
    "password={$db_information['db_password']} " .
    "port={$db_information['db_port']}");

class ConfigRegistry
{
    public const max_result_display_rows = 10000;
    public const application_name = "sead";
    public const cache_seq = "metainformation.file_name_data_download_seq";
    public const filter_by_text = true;
    public const client_result_module_path = "client/result_modules";
    public const cache_dir = __DIR__ . "/../../api/cache";

    public static function getServerName() {
        return $_SERVER['SERVER_NAME'];
    }

    public static function getServerPrefixPath() {
        $path = $_SERVER['PHP_SELF'];
        $pathinfo = pathinfo($path);
        return $pathinfo['dirname']."/";
    }

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

    public static function generateSessionKey(){
        $ip = $_SERVER['REMOTE_ADDR'];
        $timeStamp = time();
        return sha1($ip . "_" . $timeStamp);
    }

    public static function getClientResultModules()
    {
        global $client_result_module_path;
        return glob(self::client_result_module_path ."/*.js");
    }
}

?>
