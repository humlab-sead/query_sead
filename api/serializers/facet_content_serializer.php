<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

class FacetContentSerializer {

    //***********************************************************************************************************************************************************************
    /*
    function: serializeFacetContent
    Make the XML being send to client
    see also:
    <serializeFacetContent>
    */
    public static function serializeFacetContent($facet_c, $action_type, $start_row, $num_row)
    {
        $xml  = "<data>\n";
        $xml .= self::serializeFacetData($facet_c, $action_type, $start_row, $num_row);
        $xml .= "   <duration><![CDATA[0]]></duration>\n";
        $xml .= "</data>\n";
        return $xml;
    }

    /*
    function: serializeFacetContent
    Make the facet content XML being send to client
    It returns the number of requested row, starting from the start_row
    It also return a scroll_to_row when text-search is being used.

    */
    private static function serializeFacetData($data, $action_type, $start_row, $num_row)
    {
        global $request_id;
        $xml = "<facets>";
        if (!empty($data)) {
            $facet = $data;
            $xml .= "<facet_c>\n";
            $xml .= "<request_id>$request_id</request_id>\n";
            $xml .= "<f_code><![CDATA[" . $facet['f_code'] . "]]></f_code>\n";
            $xml .= "<report><![CDATA[" . $facet['report'] . "]]></report>\n";
            $xml .= "<report_html><![CDATA[" . $facet['report_html'] . "]]></report_html>\n";
            $xml .= "<report_xml>" . $facet['report_xml'] . "</report_xml>\n";
            $xml .= "<count_of_selections>" . $facet['count_of_selections'] . "</count_of_selections>\n";
            $xml .= "<range_interval>" . $facet['range_interval'] . "</range_interval>\n";
            $xml .= "<total_number_of_rows><![CDATA[" . $facet['total_number_of_rows'] . "]]></total_number_of_rows>\n";
            if ($action_type == "populate_text_search") {
                if ($start_row <= ($num_row / 2)) {
                    $scroll_to_row = $start_row;
                    $start_row = 0;
                } else {
                    $scroll_to_row = $start_row;
                    $start_row = $start_row - round(($num_row / 2));
                }
                $xml .= "<start_row>" . $start_row . "</start_row>";
                $xml .= "<scroll_to_row>" . $scroll_to_row . "</scroll_to_row>";
            } else {
                $xml .= "<start_row>" . $start_row . "</start_row>";
                $xml .= "<scroll_to_row>" . $start_row . "</scroll_to_row>";
            }
            $xml .= "<action_type><![CDATA[" . $action_type . "]]></action_type>";
            $xml .= "<rows>\n";
            $row_counter = 0;
            if (!empty($facet['rows'])) {
                for ($i = $start_row; $i <= $start_row + $num_row && count($facet['rows']) > $i && !empty($facet['rows'][$i]); $i++) {
                    $row = $facet['rows'][$i];
                    if ($row['direct_counts'] != '') {
                        $xml .= "<row>\n";
                        // make a list of values of different type
                        $xml .= "<values>\n";
                        if (!empty($row["values"])) {
                            foreach ($row["values"] as $value_type => $this_value) {
                                $xml .= "<value_item>\n";
                                $xml .= "<value_type><![CDATA[" . $value_type . "]]></value_type>\n";
                                $xml .= "<value_id><![CDATA[" . $i . "]]></value_id>\n";
                                $xml .= "<value><![CDATA[" . $this_value . "]]></value>\n";
                                $xml .=" </value_item>\n";
                            }
                        }
                        $xml .= "</values>\n";
                        $xml .= "<name><![CDATA[" . $row['name'] . "]]></name>\n";
                        $xml .= "<direct_counts><![CDATA[" . $row['direct_counts'] . "]]></direct_counts>\n";
                        $xml .= "</row>\n";
                        $row_counter++;
                    }
                }
            }
            $xml .= "</rows>\n";
            $xml .= "<rows_num>" . $row_counter . "</rows_num>";
            $xml .= "</facet_c>\n";
        }
        $xml .= "</facets>\n";
        return $xml;
    }

}
?>
