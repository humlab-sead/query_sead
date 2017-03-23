<?php

class FacetContentLoader {

    protected function compileIntervalQuery($conn, $params, $f_code)
    {
        return [ "interval" => NULL, "query" => NULL ];
    }

    protected function getFacetCategoryCount($conn, $f_code, $params, $interval_query, $direct_count_table, $direct_count_column)
    {
        return NULL;
    }

    /*
    Function:  get_facet_content
    function to obtain content of facets to the right of the current one
    Parameters:
    conn , database connection
    params - all view-state info.

    Returns:
    * facet query report, sql only
    * Selection in the particular  facets
    * start row of the rows requested
    * number of rows requested
    * total number of rows available
    row structure:
    *   row id
    *   display name
    *   (direct) count 1 number of geo-units or what is being defined in fb_def.php
    *   (indirect count 2 number of time-periods or  what is being defined in fb_def.php
    Preperation function:
    <fb_process_params>

    Functions used by interval facets:
    <getRangeQuery>

    Functions used by discrete facets:
    <get_discrete_query>
    <get_discrete_counts>

    Post functions:
    <build_xml_response>
    */  
    public function get_facet_content($conn, $params)
    {
        global $direct_count_table, $direct_count_column, $indirect_count_table, $indirect_count_column,
        $facet_definition;
        $facet_content = $search_string = $query_column_name = "";
        $f_code = $params["requested_facet"];
        $query_column = $facet_definition[$f_code]["id_column"];
        $sort_column = $query_column;

        // compute the intervall for a number of histogram items if it is defined as range facet
        list($interval, $interval_query) = $this->compileIntervalQuery($conn, $params, $f_code);

        $rs2 = Connectionhelper::query($conn, $interval_query);

        $facet_contents[$f_code]['f_code'] = $f_code;
        $facet_contents[$f_code]['range_interval'] = $interval;
        $facet_contents[$f_code]['f_action'] = $params['f_action'][1];
        $facet_contents[$f_code]['start_row'] = $params[$f_code]['facet_start_row'];
        $facet_contents[$f_code]['rows_num'] = $params[$f_code]['facet_number_of_rows'];
        $facet_contents[$f_code]['total_number_of_rows'] = pg_numrows($rs2);

        pg_result_seek($rs2, 0);

        if (isset($direct_count_column) && !empty($direct_count_column)) {
            $direct_counts = $this->getFacetCategoryCount($conn, $f_code, $params, $interval_query, $direct_count_table, $direct_count_column);
        }

        // FIXME! THIS CAN NOT WORK! SEE USE BELOW!
        // add extra information to a facet
        // if (isset($facet_definition[$f_code]["extra_row_info_facet"])) {
        //     $extra_row_info = $this->getExtraRowInfo($facet_definition[$f_code]["extra_row_info_facet"], $conn, $params);
        // }

        $count_of_selections = computeUserSelectItemCount($params, $f_code);
        $tooltip_text = ($count_of_selections != 0) ? generateUserSelectItemHTML($params, $f_code) : "";

        $facet_contents[$f_code]['report'] = " Current filter   <BR>  ". $tooltip_text."  ".SqlFormatter::format($interval_query, false)."  ; \n  " . ($direct_counts["sql"] ?? "") . " ;\n";
        $facet_contents[$f_code]['report_html'] = $tooltip_text;
        $facet_contents[$f_code]['count_of_selections'] = $count_of_selections;
        $facet_contents[$f_code]['report_xml'] = "";

        pg_result_seek($rs2, 0);

        // add values and values types
        $row_counter = 0;
        while ($row = pg_fetch_assoc($rs2)) {
            $au_id = $row["id"];
            $facet_contents[$f_code]['report'] .= $this->getCategoryItemReport($row);
            $facet_contents[$f_code]['rows'][$row_counter]['values'] = $this->getCategoryItemValue($row);
            $facet_contents[$f_code]['rows'][$row_counter]['name'] = $this->getCategoryItemName($row /*, $extra_row_info */);
            $facet_contents[$f_code]['rows'][$row_counter]['direct_counts'] = $direct_counts["list"]["$au_id"] ?? "0";
            $row_counter++;
        }
        return $facet_contents;
    }

    protected function getCategoryItemValue($row)
    {
        return NULL;
    }

    protected function getCategoryItemName($row)
    {
        return NULL;
    }

    protected function getCategoryItemReport($row)
    {
        return NULL;
    }

// FIXME SEE ABOVE
//     private function getExtraRowInfo($f_code, $conn, $params)
//     {
//         global $facet_definition;
//         $query_column = $facet_definition[$f_code]["id_column"];
//         $f_list = getKeysOfActiveFacets($params);
//         $query = get_query_clauses($params, $f_code, $data_tables, $f_list);
//         $query_column_name = $facet_definition[$f_code]["name_column"];
//         $sort_column = $facet_definition[$f_code]["sort_column"];
//         $tables = $query["tables"];
//         $query_joins = (trim($query["joins"]) != '') ? " And " .  $query["joins"] : "";
//         $where_clause = (trim($query["where"]) != '')  ? " And " . $query["where"] : "";

//         $q1 = <<<EOS
//             Select Distinct id, name
//             From (
//                 Select $query_column as id , Coalesce($query_column_name,'No value') as name, $sort_column as sort_column
//                 From $tables 
//                 Where 1 = 1 
//                 $where_clause
//                 $query_joins
//                 Group By Coalesce($query_column_name,'No value'), id, sort_column
//                 Order By sort_column
//             ) as tmp
// EOS;
//         $rs2 = ConnectionHelper::execute($conn, $q1);
//         while ($row = pg_fetch_assoc($rs2)) {
//             $au_id = $row["id"];
//             $extra_row_info[$au_id] = $row["name"];
//         }
//         return $extra_row_info;
//     }

}

class RangeFacetContentLoader extends FacetContentLoader {

   // compute max and min for range facet
    /*
    Function: computeRangeLowerUpper
    Get the min and max values of filter-variable from the database table.
    */
    private function computeRangeLowerUpper($conn, $facet)
    {
        global $facet_definition;
        $query_column = $facet_definition[$facet]["id_column"];
        $query_table = $facet_definition[$facet]["table"];
        $q = "select max($query_column) as max, min($query_column) as min from $query_table";
        $row = ConnectionHelper::queryRow($conn, $q);
        $facet_range["upper"] = $row["max"];
        $facet_range["lower"] = $row["min"];
        return $facet_range;
    }

    /*
    function: getLowerUpperLimit
    Gets the lower and limits in range-filter so the correct intervals can be computed.
    Uses the clients setting if exists, otherwise get it from the database.
    */
    // TODO: $facet is actually a $facet_key, change to real facet object instead (removed global dependencies above)
    private function getLowerUpperLimit($conn, $params, $facet)
    {
        $f_selected = getUserSelectItems($params);

        if (!isset($f_selected[$facet])) {
            foreach ($f_selected[$facet] as $skey => $selection_group) { // dig into the groups of selections of the facets
                foreach ($selection_group as $skey2 => $selection) { // dig into each group
                    foreach ($selection as $skey3 => $selection_bit) { // dig into the particular selection ie type and value
                        $selection_bit = (array) $selection_bit;
                        $limits[$selection_bit["selection_type"]] = $selection_bit["selection_value"];
                    }
                }
            }
        } else {
            // If the limits are not set in the facet_xml from the client then use the min and max values from the database
            $limits = $this->computeRangeLowerUpper($conn, $facet);
        }
        return $limits;
    }

    /*
    function: getRangeQuery
    get the sql-query for facet with interval data by computing a sql-query by adding the interval number into a sql-text
    */

    private function getRangeQuery($interval, $min_value, $max_value, $interval_count)
    {
        $pieces = array();
        $interval_counter = 0;
        $lower = $min_value;
        while ($interval_counter <= $interval_count && $lower <= $max_value) {
            $lower = $min_value + $interval * $interval_counter;
            $upper = $min_value + $interval * $interval_counter + $interval;
            $pieces[] = "select " . $lower . " as lower, " . $upper . " as upper, '" . $lower . "=>" . $upper . "'::text as id,'' as name";
            $interval_counter++;
        }
        $q1 = implode("\nunion all ",$pieces);
        return $q1;
    }

    protected function compileIntervalQuery($conn, $params, $f_code) {
        // get the limits from the client if existing otherwize get the min and max from the database
        // this is need to match the client handling of the intervals in facet.range.js
        // use the limits to define the size of the interval
        $limits = $this->getLowerUpperLimit($conn, $params, $f_code);
        $min_value = $limits["lower"];
        $max_value = $limits["upper"];
        $interval_count = 120; // should it be lower maybe??? when there is no data
        $interval = floor(($limits["upper"] - $limits["lower"]) / $interval_count);
        if ($interval <= 0) {
            $interval = 1;
        }
        // derive the interval as sql-query although it is not using a table...only a long sql with select the values of the interval
        $q1 = $this->getRangeQuery($interval, $min_value, $max_value, $interval_count);
        return [ "interval" => $interval, "query" => $q1 ];
    }

    protected function getFacetCategoryCount($conn, $f_code, $params, $interval_query, $direct_count_table, $direct_count_column)
    {
        return get_range_counts($conn, $f_code, $params, $interval_query, $direct_count_table, $direct_count_column);
    }

    protected function getCategoryItemValue($row)
    {
        return array("lower" => $row["lower"], "upper" => $row["upper"]);
    }

    protected function getCategoryItemName($row)
    {
        return $row["lower"] . "  to " . $row["upper"];
    }

    protected function getCategoryItemReport($row)
    {
        return "\n " . $this->getCategoryItemName($row);
    }

}

// depends on: getKeysOfActiveFacets, get_query_clauses
class DiscreteFacetContentLoader extends FacetContentLoader {

    function getTextFilterClause($facet_params, $query_column_name)
    {
        global $filter_by_text;
        $find_str = trim($facet_params["facet_collection"][$facet_params["requested_facet"]]["facet_text_search"]);
        if ($find_str == "undefined") {
            $find_str = "%";
        }
        return (!empty($find_str) && $filter_by_text == true) ? $query_column_name . " ILIKE '" . $find_str . "' AND \n" : "";
    }

    protected function compileIntervalQuery($conn, $facet_params, $f_code)
    {
        global $direct_count_table, $direct_count_column, $facet_definition;

        $f_code = $facet_params["requested_facet"];

        $f_list = getKeysOfActiveFacets($facet_params);
        $query = get_query_clauses($facet_params, $f_code, $data_tables, $f_list);

        $query_column_name = $facet_definition[$f_code]["name_column"];
        $query_column = $facet_definition[$f_code]["id_column"];
        $query_joins = $query["joins"];
        $sort_column = $facet_definition[$f_code]["sort_column"];
        $sort_order = $facet_definition[$f_code]["sort_order"];
        $find_cond = $this->getTextFilterClause($facet_params, $query_column_name);
        $tables = $query["tables"];
        $where_clause = (trim($query["where"]) != '')  ?  " and \n " . $query["where"] : "";
        $group_by_columns = (!empty($sort_column) ? "$sort_column, " : "") . "$query_column, $query_column_name ";
        $sort_clause = (!empty($sort_column)) ? "order by $sort_column $sort_order" : "";

        $q1 =<<<EOT
            select $query_column as id , $query_column_name as name
            from $tables $query_joins
            where $find_cond 1 = 1
              $where_clause
            group by $group_by_columns
            $sort_clause
EOT;

        return [ "interval" => 1, "query" => $q1 ];
    }

    protected function getFacetCategoryCount($conn, $f_code, $params, $interval_query, $direct_count_table, $direct_count_column)
    {
        return get_discrete_counts($conn, $f_code, $params, NULL, $direct_count_table, $direct_count_column);
    }

    protected function getCategoryItemValue($row)
    {
        return array("discrete" => $row["id"]);
    }

    protected function getCategoryItemName($row)
    {
        return $row["name"];
        // FIXME! THIS CAN NOT WORK! CONCAT NAME TO AN ARRAY???
        // if (!empty($extra_row_info)) {
        //     $name_to_display.="(" . $extra_row_info . ")";
        // }
    }

}


$facet_content_loaders = array("discrete" => new DiscreteFacetContentLoader(), "range" => new RangeFacetContentLoader());

?>