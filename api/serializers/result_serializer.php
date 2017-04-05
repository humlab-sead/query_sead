<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

class ResultSerializer {

}

class ListDataIterator implements Iterator {

    private $dataIter;
    public $columns;
    private $position;
    private $currentItem;

    function __construct($dataIter, $columns) {
       $this->dataIter = $dataIter;
       $this->columns = $columns;
       $this->position = -1;
       $this->currentItem = NULL;
    }

    function next()
    {
        $row = $this->dataIter->next();
        if ($row) {
            $this->position += 1;
            $this->currentItem = $this->createItem($row);
        } else {
            $this->currentItem = NULL;
        }
        return $this->currentItem;
    }
    public function current ()
    {
        return $this->currentItem;
    }

    public function key()
    {
        return $this->position;
    }
    public function rewind()
    {
        if ($this->position == -1)
            $this->next();
    }

    public function valid()
    {
        return isset($this->currentItem);
    }

    function createItem($row)
    {
        $data["row_id"] = $this->position;
        $columnCounter = 0;
        foreach ($row ?? [] as $row_item) {
            $header = $this->columns[$columnCounter];
            $columnType = $header["result_column_type"];
            if ($columnType == "sort_item") {
                continue;
            }
            $is_link = $columnType == "link_item" || $columnType == "link_item_filtered";
            $data[$columnCounter] = [
                "value_type" => $columnType,
                "result_column_title" => $header["result_column_title"],
                "result_column_title_extra" => $header["result_column_title_extra"],
                "value" => $row_item,
                "link_url" => $is_link ? $header["link_url"] /* . "=$row_item" ?? "" */ : NULL,
                "link_label" => $is_link ? $header["link_label"] ?? $row_item : NULL
            ];
            $columnCounter++;
        }
        return $data;
    }

    public function count()
    {
        return method_exists($this->dataIter, "count") ? $this->dataIter->count() : NULL;
    }

}

class ListResultSerializer extends ResultSerializer {

    protected function getColumnMetaData($resultConfig)
    {
        global $result_definition;
        $column_counter = 0;
        foreach ($resultConfig["items"] ?? [] as $result_params_key) {
            foreach ($result_definition[$result_params_key]["result_item"] as $result_column_type => $result_definition_item) {
                foreach ($result_definition_item as $item_type => $result_item) {
                    if ($result_column_type == "sort_item") {
                        continue;
                    }
                    $column_meta_data[$column_counter] = [
                        "result_column_type" => $result_column_type,
                        "link_url" => $result_item["link_url"],
                        "link_label" => $result_item["link_label"],
                        "result_column_title" => $result_item["text"],
                        "result_column_title_extra_info" => ($result_column_type == 'count_item') ? "#items with value" : ""
                    ];
                    $column_counter++;
                }
            }
        }
        return $column_meta_data;
    }

    public function serialize($rsIter, $facetConfig, $resultConfig, $facetStateId, $extra)
    {
        $columns = $this->getColumnMetaData($resultConfig);
        $dataIter = new ListDataIterator($rsIter, $columns);
        $serialized_data = $this->serializeData($dataIter, $facetConfig, $resultConfig, $facetStateId);
        return $serialized_data;
    }

    protected function serializeData($dataIter, $facetConfig, $resultConfig, $facetStateId)
    {
        return "";
    }   
}

class XmlListResultSerializer extends ListResultSerializer {

    private function xml_encode($dataIter, $indent = false, $i = 0)
    {
        if (!$i) {
            /*   $data = ''.($indent?"\r\n":'').'<root>'.($indent?"\r\n":'');*/
        } else {
            $data = '';
        }
        foreach ($dataIter as $tag => $value) {
            if (is_numeric($tag)) {
                $tag = 'item';
            }
            $data .= ($indent?str_repeat("\t", $i):'').'<'.$tag.'>';
            if (is_array($value)) {
                $data .= ($indent?"\r\n":'').xml_encode($value, $indent, ($i+1)).($indent?str_repeat("\t", $i):'');
            } else {
                $data .= "<![CDATA[" . $value . "]]>";
            }
            $data .= '</'.$tag.'>'.($indent?"\r\n":'');
        }
        return $data;
    }

    protected function serializeData($dataIter, $facetConfig, $resultConfig, $facetStateId)
    {
        return xml_encode($dataIter);
    }

}

class HtmlListResultSerializer extends ListResultSerializer {
 
    private function renderHeader($headers)
    {
        foreach ($headers as $item) {
            $html .= "<th>{$item['result_column_title']} {$item['result_column_title_extra_info']}</th>";
        }
        return $html;
        //return array_reduce($headers, function($r, $x) { return $r . "<th>{$x['result_column_title']} {$x['result_column_title_extra_info']}</th>";}, "");
    }

    private function renderRows($dataIter, $cache_id, $maxRows)
    {
        foreach ($dataIter as $row) {
            if ($dataIter->key() > $maxRows) {
                break;
            }
            $html_table .= "<tr>";
            foreach ($row as $item) {
                $html_table .= "<td>" . $this->renderItem($item, $cache_id) . "</td>\n";
            }
            $html_table .= "</tr>\n";
        }
        return $html_table;
    }

    private function renderItem($item, $cache_id)
    {
        $is_link = $item["value_type"] == "link_item" || $item["value_type"] == "link_item_filtered";
        if ($is_link) {
            $cache_param = ($item["value_type"] == "link_item_filtered") ? "&cache_id=$cache_id" : "";
            $text = "<a href=\"{$item['link_url']}={$item['value']}{$cache_param}\" title=\"info\" target=\"blank\" >{$item['link_label']}</a>";
        } else {
            $text = $item['value'];
        }
        return $text;
    }

    protected function serializeData($dataIter, $facetConfig, $resultConfig, $facetStateId)
    {
        $maxRows = ConfigRegistry::getMaxResultDefaultRows() ?? 10000;

        $recordCount = $dataIter ? $dataIter->count() : 0;
        $columnCount = count($resultConfig["items"]);
        
        $header = $dataIter ? $this->renderHeader($dataIter->columns) : "";
        $table_body = $dataIter ? $this->renderRows($dataIter, $facetStateId, $maxRows) : "";
        $visibility = $recordCount < $maxRows ? "" : "none";

        $html = <<<EOS
            Your search resulted in $recordCount records<span style="display: '{$visibility}';">. The first {$maxRows} are</span> displayed below.
            <a href='/api/report/get_data_table_text.php?cache_id=$facetStateId' id='download_link'>Save data as text to file.</a>
            <a href='/api/report/get_data_table.php?cache_id=$facetStateId' id='download_link2'>Save data to Excel.</a>
            <table id='result_output_table'>
            <thead>
                <tr>$header</tr>
            </thead>
            <tbody>
                $table_body
            </tbody>
            <!-- data array -->
            <!-- array -->     
            </table>
EOS;
        return $html;
    }
}

class MapResultSerializer extends ResultSerializer 
{
    public function serialize($data, $facetConfig, $resultConfig, $facetStateId, $extra)
    {
        $out  = "<aggregation_code>{$resultConfig['aggregation_code']}</aggregation_code>\n<result_html></result_html>";
        //$out .= "<sql_info><![CDATA[$q]]></sql_info>";
        $out .= "<sql_info></sql_info>";
        $out .= "<points>";
        while (($row = $data->next())) 
        {
            $out .= "<point>\n";
            $out .= " <name><![CDATA[".$row["name"]."]]></name>\n";
            $out .= " <id>".$row["id_column"]."</id>\n";
            $out .= " <latitude><![CDATA[".$row['latitude_dd']."]]></latitude>\n";
            $out .= " <longitude><![CDATA[".$row['longitude_dd']."]]></longitude>\n";
            $out .= " <filtered_count><![CDATA[". $extra['filtered_count'][$row["id_column"]] ."]]></filtered_count>\n";
            $out .= " <un_filtered_count><![CDATA[". $extra['un_filtered_count'][$row["id_column"]] ."]]></un_filtered_count>\n";
            $out .= "</point>\n";
        }
        $out .= "</points>\n";
        return $out;
    }
}

?>