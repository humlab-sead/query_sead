<?php
class FacetPicksSerializer
{
        /*
    Function: FacetPicksSerializer::toHTML
    Serializes result from FacetConfig->collectFacetPicks to HTML.
    */
    public static function toHTML($selection_rows_matrix)
    {
        $selection_html = "";
        foreach ($selection_rows_matrix as $facetCode => $data) {
            $display_title = $data['display_title'];
            $facet_html = "";
            foreach ($data['selections'] ?? [] as $value) {
                $facet_html .= "<TR><TD>$value</TD></TR>";
            }
            $selection_html .=
                "<TD style=\"vertical-align:top\">" .
                "<TABLE class=\"generic_table\" ><TR><TD class=\"facet_control_bar_button\" >$display_title</TD></TR>" .
                     $facet_html .
                "</TABLE>" .
                "</TD>";
        }
        $html = <<<EOS
            <TABLE class="generic_table">
            <TR><TD>Current selections</TD><TR>
            <TR>
                $selection_html
            </TR>
            </TABLE>
EOS;
        return $html;
    }
}
?>
