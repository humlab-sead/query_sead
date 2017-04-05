<?php

class ViewStateSerializer {

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

}

?>