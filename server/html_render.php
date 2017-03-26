<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

class RenderHTML {

    public static function render_html_header_from_array($table_id, $header_array)
    {
        $html="<TABLE id=\"".$table_id."\">\n";
        $html.="<thead><TR>\n";
        foreach ($header_array as $data_item_counter => $data_element) {
            $html.="<th>".$data_element["result_column_title"]." ".$data_element["result_column_title_extra_info"]."</th>";
        }
        $html.="</TR></thead>\n";
        return $html;
    }

}
?>