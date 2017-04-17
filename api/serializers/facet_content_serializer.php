<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

class FacetContentSerializer {

    //***********************************************************************************************************************************************************************
    /*
    function: serializeFacetContent
    */
    public static function serializeFacetContent($facetContent, $action_type, $start_row, $num_row)
    {
        $xml  = "<data>\n";
        $xml .= self::serializeFacetData($facetContent, $action_type, $start_row, $num_row);
        $xml .= "   <duration><![CDATA[0]]></duration>\n";
        $xml .= "</data>\n";
        return $xml;
    }

    private static function serializeFacetData($facetContent, $action_type, $start_row, $num_row)
    {
        if (empty($facetContent)) {
            return "<facets></facets>";
        }
        $scroll_to_row = $start_row;
        if ($action_type == "populate_text_search") {
            $start_row = ($start_row <= ($num_row / 2)) ? 0 : ($start_row - round(($num_row / 2)));
        }
        $request_id = "0"; // FIXME - check where it us stored!"
        $xml  = "<facets>" .
                "<facet_c>\n" .
                "<request_id>$request_id</request_id>\n" .
                "<f_code><![CDATA[{$facetContent->facetCode}]]></f_code>\n" .
                "<report><![CDATA[{$facetContent->report}]]></report>\n" .
                "<report_html><![CDATA[{$facetContent->reportHtml}]]></report_html>\n" .
                "<report_xml>{$facetContent->reportXml}</report_xml>\n" .
                "<count_of_selections>{$facetContent->countOfSelections}</count_of_selections>\n" .
                "<range_interval>{$facetContent->interval}</range_interval>\n" .
                "<total_number_of_rows><![CDATA[{$facetContent->totalRowCount}]]></total_number_of_rows>\n" .
                "<start_row>$start_row</start_row>\n" .
                "<scroll_to_row>$scroll_to_row</scroll_to_row>\n" .
                "<action_type><![CDATA[$action_type]]></action_type>\n" .
                "<rows>\n";
        $row_counter = 0;
        for ($i = $start_row; $i <= $start_row + $num_row && $facetContent->totalRowCount > $i && !empty($facetContent->rows[$i]); $i++) {
            $row = $facetContent->rows[$i];
            if ($row['direct_counts'] == '')
                continue;
            $xml .= "<row>\n" .
                    "<values>\n";
            foreach ($row["values"] ?: [] as $value_type => $this_value)
                $xml .= "<value_item>\n" .
                        "<value_type><![CDATA[$value_type]]></value_type>\n" .
                        "<value_id><![CDATA[$i]]></value_id>\n" .
                        "<value><![CDATA[$this_value]]></value>\n" .
                        "</value_item>\n";
            $xml .= "</values>\n" .
                    "<name><![CDATA[{$row['name']}]]></name>\n" .
                    "<direct_counts><![CDATA[{$row['direct_counts']}]]></direct_counts>\n" .
                    "</row>\n";
            $row_counter++;
        }
        $xml .= "</rows>\n" .
                "<rows_num>{$row_counter}</rows_num>" .
                "</facet_c>\n" .
                "</facets>\n";
        return $xml;
    }

}
?>
