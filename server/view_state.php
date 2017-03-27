<?php

require_once __DIR__ . "/config/bootstrap_application.php";
require_once __DIR__ . "/connection_helper.php";

class ViewState {

    public static function toXML($rows)
    {
        $xml  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= "<view_states>\n";
        foreach ($rows as $row)
        {
            $xml .= "<view_state>\n";
            $xml .= "<id>" . $row["view_state_id"] . "</id>\n";
            $xml .= "<created>" . $row["creatation_date"] . "</created>\n";
            $xml .= "</view_state>\n";
        }
        $xml .= "</view_states>";
        return $xml;
    }

    public static function saveViewState($session_id, $view_state)
    {
        global $view_state_table;
        $conn = ConnectionHelper::createConnection();
        $q = "Insert Into $view_state_table (view_state, session_id) Values ('$view_state', '$session_id') Returning view_state_id";
        $rs = ConnectionHelper::query($conn, $q);
        $row = pg_fetch_assoc($rs);
        $view_state_id = $row["view_state_id"];
        return $view_state_id;
    }

    public static function getIndex($session_id)
    {
        global $view_state_table;
        $conn = ConnectionHelper::createConnection();
        $q = "select * from $view_state_table where session_id = '$session_id' order by view_state_id desc limit 5";
        $index = ConnectionHelper::queryRows($conn, $q);
        return $index;
    }

    public static function getViewState($view_state_id)
    {
        global $view_state_table;
        $conn = ConnectionHelper::createConnection();
        $q = "select * from $view_state_table where view_state_id = $view_state_id";
        $row = ConnectionHelper::queryRow($conn, $q);
        return $row ? $row["view_state"] : null;
    }
}

?>