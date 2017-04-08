<?php

require_once __DIR__ . "/lib/SqlFormatter.php";

class RecordSetIterator {

    public $rs;
    function __construct($rs) {
        $this->rs = $rs;
    }

    function next()
    {
        return pg_fetch_assoc($this->rs);
    }

    function count()
    {
        return pg_num_rows($this->rs);
    } 
}

class ConnectionHelper
{
    private static function trace($sql, $context="")
    {
        $stack = debug_backtrace(); 
        $filename = '../sql.log';
        $stack_text = ""; 
        $header = str_pad("#", 120, "#");
        $divider = ""; 
        $datetime = new DateTime('NOW');
        $now = $datetime->format('Y-m-d H:i:s');
        $sql = SqlFormatter::format($sql, false);
        while (list($i, $x) = each($stack)) {
            $source_file = str_pad(basename($x['file']), 35);
            $source_line = str_pad($x['line'], 4);
            $class_name  = $x['class'];
            $function    = (empty($class_name) ? "" :  "$class_name::") . $x['function'];
            $stack_text .=  ($x['class'] == __CLASS__) ? "" :  "   $i $source_file $source_line $function\n";
        }
        file_put_contents($filename,
            "\n$header\nTimestamp: $now\n\n" .
            "   # filename                            line function\n" .
            "   ---------------------------------------------------\n" .
            "$stack_text" .
            "$divider\n" .
            "$sql\n", FILE_APPEND | LOCK_EX);
    }

    public static function createConnection()
    {
        if (!($conn = pg_connect(CONNECTION_STRING))) {
            error_log("Error: pg_connect failed.\n");
            exit;
        }
        return $conn;
    }

    public static function execute($conn, $q, $context="")
    {
        self::trace($q, $context);
        if (($rs = pg_exec($conn, $q)) <= 0) {
            error_log("Error: cannot execute:  " . SqlFormatter::format($q, false) . "  \n");
            pg_close($conn);
            exit;
        }
        return $rs;
    }

    public static function query($conn, $q, $context="")
    {
        self::trace($q, $context);
        if (($rs = pg_query($conn, $q)) <= 0) {
            $where = empty($context) ? "" : " in \"$context\"";
            error_log("Error$where: cannot execute query: " . SqlFormatter::format($q, false) . "\n");
            pg_close($conn);
            exit;
        }
        return $rs;
    }

    public static function queryIter($conn, $q, $context="")
    {
        return new RecordSetIterator(self::query($conn, $q, $context));
    }

    public static function queryRows($conn, $q)
    {
        $rs = ConnectionHelper::query($conn, $q);
        $rows = pg_fetch_all($rs);
        return $rows;
    }

    public static function queryRow($conn, $q)
    {
        $rs = ConnectionHelper::query($conn, $q);
        $row = pg_fetch_assoc($rs);
        return $row;
    }
}
?>
