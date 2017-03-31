<?php

require_once(__DIR__ . '/../../server/cache_helper.php');
require_once(__DIR__ . '/../../server/connection_helper.php');
require_once(__DIR__ . '/../../server/facet_config.php');
require_once(__DIR__ . '/../../server/query_builder.php');

/**
 * function: get_f_code_filter_query
 * get the unique id to be used to filter the report based on user selection in QSEAD
 * 
 * globals:
 * -  $facet_definition
 * 
 * params: 
 * - $cache_id id of reference to facet_xml file
 * - $facetCode facet to be rendered so id can be used in SQL
 * - $data_table any extra tables?
 * 
 * return: query string
 */
function get_f_code_filter_query($cache_id, $facetCode = "result_facet", $data_table = null) {
    global $facet_definition;

    $conn = ConnectionHelper::createConnection();

    $facet_xml = CacheHelper::get_facet_xml($cache_id);

    $facet_params = FacetConfigDeserializer::deserializeFacetConfig($facet_xml);
    $facet_params = FacetConfig::removeInvalidUserSelections($conn, $facet_params);

    $tmp_list = FacetConfig::getKeysOfActiveFacets($facet_params);
    $tmp_list[] = $facetCode; //Add  as final facet

    $query_info = QueryBuildService::compileQuery($facet_params, $facetCode, $data_table, $tmp_list);
    $query_column = $facet_definition[$facetCode]["id_column"];
    $q = "select distinct $query_column  from " . $query_info["tables"];
    if (!empty($query_info["joins"])) {
        $q .= " " . $query_info["joins"];
    }
    if (!empty($query_info["where"])) {
        $q .= " where " . $query_info["where"];
    }

    return $q;
}
/*
 * function: get_select_info_as_html
 * 
 * params: $cache_id ref to filter by user
 * returns: html text about selection in filters
 */
function get_select_info_as_html($conn, $cache_id) {
    $facet_xml = CacheHelper::get_facet_xml($cache_id);
    $facet_params = FacetConfigDeserializer::deserializeFacetConfig($facet_xml);
    $facet_params = FacetConfig::removeInvalidUserSelections($conn, $facet_params);
    return FacetConfig::generateUserSelectItemHTML($facet_params);
}

class base_reporter {
    /*
     * function: run_query
     * runs query to database, exits if fails
     * return: resultset
     */
    protected function run_query($conn, $q) {
        return ConnectionHelper::query($conn, $q, get_class($this));
    }
}

/*
 * Class: sample_group_reporter
 * Report function on sample_group level
 */
class sample_group_reporter extends base_reporter {

    private $reporter;
    private $sample_group_query_object;

    function __construct() {
        $this->sample_group_query_object = new sample_group_query();
        $this->reporter = new report_module();
    }

    /*
     * Function: dataset_report
     * Makes dataset report for a sample group or multiple sample groups if sample_group id is not set.
     */
    function dataset_report($conn, $sample_group_id, $cache_id) {
        $q = $this->sample_group_query_object->render_dataset_query($sample_group_id);
        $rs = $this->run_query($conn, $q);
        $html.= "<p><b>List of dataset</b></p>\n";
        $html.= $this->reporter->format_rs($rs);
        return $html;
    }

    /*
     * function: sample_group_agg_summary
     * Report that gives list sample group for a given filter given by "cache_id"
     */

    function sample_group_agg_summary($conn, $sample_group_id, $cache_id) {
        $q = $this->sample_group_query_object->get_sample_group_query($sample_group_id, $cache_id);
        $rs = $this->run_query($conn, $q);
        $html.= $this->reporter->format_rs($rs);
        return $html;
    }

    /* function: sample_summary
     * Report that get the site of sample group and the info for the sample gruop itself
     */

    function sample_summary($conn, $sample_group_id, $cache_id) {
        $q = $this->sample_group_query_object->get_site_query($sample_group_id, $cache_id);
        $rs = $this->run_query($conn, $q);
        while ($row = pg_fetch_assoc($rs)) {
            $html.="<p>Site : <b>" . $row["site_name"] . "</b></p>\n";
        }
        $q = $this->sample_group_query_object->get_sample_group_query($sample_group_id, $cache_id);
        $rs = $this->run_query($conn, $q);
        $html.= $this->reporter->format_rs($rs, 'site_id', 'show_site_details.php?', $cache_id);
        return $html;
    }

    function get_sample_group_methods($conn, $sample_group_id, $cache_id)
    {
    
        $q= $this->sample_group_query_object->render_sample_group_methods_query($sample_group_id,$cache_id);
        $rs = $this->run_query($conn, $q);
        $counter=0;
        while ($row = pg_fetch_assoc($rs)) {
            $methods[$counter]["method_id"]=$row["method_id"];
            $methods[$counter]["method_name"]=$row["method_name"];
            $counter++;
        }
        
        return $methods;
        
      }
    /*
     * function: species_report
     * Makes a transposed report of the abundances of species for each physical sample
     */

    function species_report($conn, $sample_group_id, $cache_id) {
        
        // get the methods for each abundance report to be used
        $method_list=$this->get_sample_group_methods($conn, $sample_group_id, $cache_id);
        if (isset($method_list))
        {
            foreach ($method_list as $mkey=>$method_obj)
            {
                $data_array = $this->sample_group_query_object->arrange_species_data($conn, $sample_group_id,
                                                                    $method_obj["method_id"], $cache_id);
                if (count($data_array) > 1) {
                    $html.="<p><b>Abundances  ".$method_obj["method_name"]."</b></p>\n";
                    $html.=$this->reporter->array_to_html($data_array);
                }
            }
        }
        return $html;
    }

    /*
     * function: measured_values_report
     * Report of values for each dataset for each physical sample, in matrix format.
     */

    function measured_values_report($conn, $sample_group_id, $cache_id) {
        $data_array = $this->sample_group_query_object->arrange_measured_values($conn, $sample_group_id, $cache_id);
        if (count($data_array) > 1) {
            $html.= "<p><b>Measured values </b></p>\n";
            $html.= $this->reporter->array_to_html($data_array);
        }
        return $html;
    }

}

/*
 * Class: site_reporter
 * functions for report in site level
 */

class site_reporter extends base_reporter {

    private $site_query_obj;
    private $reporter;

    function __construct() {
        $this->site_query_obj = new site_query();
        $this->reporter = new report_module();
    }

    /*
     * function: dating_report
     * Chronologies
     */

    function dating_report($conn, $site_id, $cache_id) {
        $q = $this->site_query_obj->get_relative_ages_query($site_id, $cache_id);
        $rs = $this->run_query($conn, $q);
        if (pg_num_rows($rs) > 1) {
            $html = "<B>Dating</B>";
            $html.=$this->reporter->format_rs($rs, null, null, $cache_id);
            return $html;
        }
        return "";
    }

    /*
     * function: dataset_report
     * Report about dataset in site
     */

    function dataset_report($conn, $site_id, $cache_id) {
        $q = $this->site_query_obj->get_dataset_query($site_id, $cache_id);

        $rs = $this->run_query($conn, $q);
        if (pg_num_rows($rs) > 1) {
            $html = "<B> Datasets </B>";
            $html.=$this->reporter->format_rs($rs, null, null, $cache_id);
            return $html;
        }
        return "";
    }

    /*
     * Function: site_info_report
     * Report with info about the site(s)
     */

    function site_info_report($conn, $site_id, $cache_id) {
        $q = $this->site_query_obj->get_site_query($site_id, $cache_id);
        //   echo $q;
        $rs = $this->run_query($conn, $q);
        if (!empty($cache_id)) {
            //$html.= "<B> SITE FILTERED</B>";
            $html.=get_select_info_as_html($conn, $cache_id) . "<BR>";
            //$html.=$this->reporter->report_selections($conn, $cache_id);
        } else {
            $html.= "<B> No filters </B>";
        }
        // http://localhost/qviz_refactor/api/report/show_site_details.php?site_id=3&cache_id=sead680&application_name=sead
        $html.= $this->reporter->format_rs($rs, 'site_id', 'show_site_details.php?', $cache_id);

        return $html;
    }

    /*
     * Function: reference_report
     * Get the title, year and author for references related to site(s)
     */

    function reference_report($conn, $site_id, $cache_id) {
        $q = $this->site_query_obj->get_reference_query($site_id, $cache_id);
        $rs = $this->run_query($conn, $q);
        if (pg_num_rows($rs) > 1) {
            $html = "<B> References </B>";
            $html.= $this->reporter->format_rs($rs, null, null, $cache_id);
            return $html;
        }
        return "";
    }

    /*
     * function: sample_group_report
     * Info about sample_group(s) for site(s)
     */

    function sample_group_report($conn, $site_id, $applicationName, $cache_id) {
        $q = $this->site_query_obj->get_sample_group_info_query($site_id, $cache_id);
        $rs = $this->run_query($conn, $q);
        $html.= "<B> Sample groups</B>";
        $html.= $this->reporter->format_rs($rs, "sample_group_id", "show_sample_group_details.php?application_name=$applicationName&", $cache_id);
        return $html;
    }

    function get_site_methods($conn, $site_id, $cache_id)
    {
    
        $q= $this->site_query_obj->render_site_methods_query($site_id,$cache_id);
        $rs = $this->run_query($conn, $q);
        $counter=0;
        while ($row = pg_fetch_assoc($rs)) {
            $methods[$counter]["method_id"]=$row["method_id"];
            $methods[$counter]["method_name"]=$row["method_name"];
            $counter++;
        }
        
        return $methods;
        
      }
    function species_report($conn, $site_id, $cache_id){
        $method_list=$this->get_site_methods($conn, $site_id, $cache_id);
        if (isset($method_list))
        {
            foreach ($method_list as $mkey=>$method_obj)
            {
                $data_array = $this->site_query_obj->arrange_species_data($conn, $site_id,
                                                                    $method_obj["method_id"], $cache_id);
                if (count($data_array) > 1) {
                    $html.="<p><b>Abundances  ".$method_obj["method_name"]."</b></p>\n";
                    $html.=$this->reporter->array_to_html($data_array);
                }
            }
        }
        return $html;
        
    }
    
     function measured_values_report($conn, $site_id, $cache_id) {
        $data_array = $this->site_query_obj->arrange_measured_values($conn, $site_id, $cache_id);
        if (count($data_array) > 1) {
            $html.= "<p><b>Measured values </b></p>\n";
            $html.= $this->reporter->array_to_html($data_array);
        }
        return $html;
    }
}

/*
 * class: report_module
 * Generic module to format arrays to html
 */

class report_module {

    // 
    public static $count_table = 1;   // Counter for unique objects

    /*
     * function: report_selections
     * Renders the filters used from QSEAD.
     * not used.... 
     */

    public function report_selections($conn, $cache_id) {

        $html = "No filtering";
        $facet_xml = get_facet_xml_from_id($cache_id);

        $facet_params = FacetConfigDeserializer::deserializeFacetConfig($facet_xml);
        $facet_params = FacetConfig::removeInvalidUserSelections($conn, $facet_params);

        $selection_matrix = FacetConfig::generateUserSelectItemMatrix($facet_params); // get the selection as matrix to be able to populate the filter sheet.
        print_r($selection_matrix);
        if (isset($selection_matrix)) {
            $html = "Criterias :";
            foreach ($selection_matrix as $facet_code => $selection_info) {
                //$objWorksheet1->setCellValueByColumnAndRow($column_counter, 4, $selection_info["display_title"]);
                $html.=$selection_info["display_title"];
//		print_r($selection_info["selections"]);
                if (isset($selection_info["selections"])) {

                    foreach ($selection_info["selections"] as $sel_key => $selection_items) {
                        // print_r($selection_items["selection_text"]);
                        if (isset($selection_items["selection_text"])) {
                            $html.=$selection_items["selection_text"];
                            //echo "info ".$selection_info["selection_text"];
                        }
                    }
                }
            }
        }
        return $html;
    }

    /**
     * function: format_data_array 
     * Arrange array to html
     * params:
     * $data_array - array to be formated type 
     * $format_type -  normally html
     * 
     * returns: string
     */
    public function format_data_array($data_array, $format_type = "html") {

        if (!isset($data_array)) {
            return "";
        }

        $html = "<table class=\"data_table\" border=\"1\">";
        $html .= "<TR>";
        $column_total = 0;

        foreach ($data_array[0] as $head_column) {
            $html.="<TD>" . $head_column . "</TD>\n";
        }
        $html .="</TR>";
        $row_count = 0;

        foreach ($data_array as $row) {
            if ($row_count > 0) {
                $html.="<TR>";
                $counter = 0;

                foreach ($row as $column_key => $column_value) {
                    if (isset($column_value)) {
                        $html.="<TD>" . $column_value . " </TD>\n";
                    } else {
                        $html.="<TD>&nbsp;</TD>\n";
                    }
                }
                $html.="</TR>\n";
            }
            $row_count++;
        }
        $html.="</table>";

        return $html;
    }

    /**
     * Function : format_rs
     * Makes html of database ressult set and add hyperlinks for specified column in the html
     * params:
     *  $rs - resultset from database query
     *  $link_table_column  - to be used for linking to related webpage $link_table_column
     *  $url_base - url for the webpage
     *  $cache_id - reference to filters  
     * 
     *  see also:
     * <get_f_code_filter_query>
     * 
     * returns: html string
     */
    public function format_rs($rs, $link_table_column = null, $url_base = null, $cache_id = null) {
        return $this->array_to_html($this::rs_to_array($rs), $link_table_column, $url_base, $cache_id);
    }

    // NOTE: Make new format_rs to replace current
    // Use same parameters
    // 1) rs_to_array
    // 2) array_to_html

    /**
     * function: rs_to_array
     * Converts query result resource to numeric array. First row in
     * the resulting array contains the keys, subsequent rows contains
     * values.
     *
     * param: 
     *  $rs - A query result resource
     * 
     *  returns: array Query result as numeric arrayArray
     */
    public static function rs_to_array($rs) {
        if (pg_num_rows($rs) === 0) {
            return "";
        }
        $array[0] = array_keys(pg_fetch_assoc($rs, 0));
        while ($row = pg_fetch_array($rs, NULL, PGSQL_NUM)) {
            array_push($array, $row);
        }
        return $array;
    }

    /**
     * function: array_to_html
     * params: 
     *  $array  - the  assumed as matrix
     *  $link_table_column - column to  be used link to other page
     *  $url_base - base link  for the other webpage
     *  $cache_id - reference to filters xml
     * 
     * returns: html string
     */
    public static function array_to_html(
    $array, $link_table_column = null, $url_base = null, $cache_id = null
    ) {
        $count = self::$count_table++;
        $html = "<div style=\"margin-bottom: 40px\">\n";
        $datatables_str = "id=\"d_table-" . $count . "\"";

        // Return empty string if array is empty
        if (empty($array)) {
            return "";
        }

        $link_key = array_search($link_table_column, $array[0]);


        $cache_arg = (empty($cache_id) ? "" : "&cache_id=$cache_id");

        // Table
        $html .= "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" $datatables_str>\n";

        // Table head
        $html .= "<thead>\n";
        $html .= "\t<tr class=\"info\">\n"; // blue
        // $html .= "\t\t<th>";

        $next_row = $array[1];
        $this_row = array_shift($array);
        //print_r($next_row);

        foreach ($this_row as $key => $header) {
            $style = !is_numeric($next_row[$key]) ? "text-align:left" : "text-align:right";
            $html.="<th style=\"$style\">" . $header . "</th>\n";
        }

        $html .= "\t</tr>\n";
        $html .= "</thead>\n";

        // Table body
        $html .= "<tbody>\n";

        while ($row = array_shift($array)) {
            $html .= "\t<tr>\n";
            foreach ($row as $key => $value) {
                $style = !is_numeric($value) ? "text-align:left" : "text-align:right";
                if ($key === $link_key) {

                    $html .= "\t\t<td style=\"$style\" ><b>";
                    $html .= "<a href=\"" . $url_base . "" . $link_table_column . "=" . $value . $cache_arg . "\">" . $value . "</a>";
                    $html .= "</b></td>\n";
                } else {
                    // check numeric

                    $html .= "\t\t<td style=\"$style\">$value</td>\n";
                }
            }
            $html .= "\t</tr>\n";
        }

        $html .= "\t</tbody>\n";

        $html .= "</table>\n";
        $html .= "</div>\n";

        return $html;
    }

}
