<?php

require_once(__DIR__ . '/facet_content_counter.php');
require_once(__DIR__ . '/cache_helper.php');
require_once(__DIR__ . '/query_builder.php');

class FacetContentLoader {

    protected function compileIntervalQuery($conn, $facetConfig, $facetCode)
    {
        return [ NULL, NULL ];
    }

    protected function getFacetCategoryCount($conn, $facetCode, $facetConfig, $interval_query)
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
    *   (direct) count 1 number of geo-units or what is being defined in bootstrap_application.php
    *   (indirect count 2 number of time-periods or  what is being defined in bootstrap_application.php
    Preperation function:
    <FacetConfigDeserializer::deserializeFacetConfig>

    Functions used by interval facets:
    <getRangeQuery>

    Functions used by discrete facets:
    <get_discrete_query>
    <get_discrete_counts>

    Post functions:
    <serializeFacetContent>
    */  
    public function get_facet_content($conn, $facetConfig)
    {
        global $direct_count_table, $direct_count_column, $indirect_count_table, $indirect_count_column,
        $facet_definition;
        $facet_content = $search_string = $query_column_name = "";
        $facetCode = $facetConfig["requested_facet"];
        $query_column = $facet_definition[$facetCode]["id_column"];
        $sort_column = $query_column;

        // compute the intervall for a number of histogram items if it is defined as range facet
        list($interval, $interval_query) = $this->compileIntervalQuery($conn, $facetConfig, $facetCode);

        $rs2 = Connectionhelper::query($conn, $interval_query);

        $facet_contents[$facetCode]['f_code'] = $facetCode;
        $facet_contents[$facetCode]['range_interval'] = $interval;
        $facet_contents[$facetCode]['f_action'] = $facetConfig['f_action'][1];
        $facet_contents[$facetCode]['start_row'] = $facetConfig[$facetCode]['facet_start_row'];
        $facet_contents[$facetCode]['rows_num'] = $facetConfig[$facetCode]['facet_number_of_rows'];
        $facet_contents[$facetCode]['total_number_of_rows'] = pg_numrows($rs2);

        pg_result_seek($rs2, 0);

        if (isset($direct_count_column) && !empty($direct_count_column)) {
            $direct_counts = $this->getFacetCategoryCount($conn, $facetCode, $facetConfig, $interval_query);
        }

        // FIXME! THIS CAN NOT WORK! SEE USE BELOW!
        // add extra information to a facet
        // if (isset($facet_definition[$facetCode]["extra_row_info_facet"])) {
        //     $extra_row_info = $this->getExtraRowInfo($facet_definition[$facetCode]["extra_row_info_facet"], $conn, $facetConfig);
        // }

        $count_of_selections = FacetConfig::computeUserSelectItemCount($facetConfig, $facetCode);
        $tooltip_text = ($count_of_selections != 0) ? FacetConfig::generateUserSelectItemHTML($facetConfig, $facetCode) : "";

        $facet_contents[$facetCode]['report'] = " Current filter   <BR>  ". $tooltip_text."  ".SqlFormatter::format($interval_query, false)."  ; \n  " . ($direct_counts["sql"] ?? "") . " ;\n";
        $facet_contents[$facetCode]['report_html'] = $tooltip_text;
        $facet_contents[$facetCode]['count_of_selections'] = $count_of_selections;
        $facet_contents[$facetCode]['report_xml'] = "";

        pg_result_seek($rs2, 0);

        // add values and values types
        $row_counter = 0;
        while ($row = pg_fetch_assoc($rs2)) {
            $au_id = $row["id"];
            $facet_contents[$facetCode]['report'] .= $this->getCategoryItemReport($row);
            $facet_contents[$facetCode]['rows'][$row_counter]['values'] = $this->getCategoryItemValue($row);
            $facet_contents[$facetCode]['rows'][$row_counter]['name'] = $this->getCategoryItemName($row /*, $extra_row_info */);
            $facet_contents[$facetCode]['rows'][$row_counter]['direct_counts'] = $direct_counts["list"]["$au_id"] ?? "0";
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
//     private function getExtraRowInfo($facetCode, $conn, $facetConfig)
//     {
//         global $facet_definition;
//         $query_column = $facet_definition[$facetCode]["id_column"];
//         $facetCodes = FacetConfig::getKeysOfActiveFacets($facetConfig);
//         $query = QueryBuildService::compileQuery($facetConfig, $facetCode, $data_tables, $facetCodes);
//         $query_column_name = $facet_definition[$facetCode]["name_column"];
//         $sort_column = $facet_definition[$facetCode]["sort_column"];
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
    private function getLowerUpperLimit($conn, $facetConfig, $facet)
    {
        $f_selected = FacetConfig::getItemGroupsSelectedByUser($facetConfig);

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
        $q1 = implode("\nunion all ", $pieces);
        return $q1;
    }

    protected function compileIntervalQuery($conn, $facetConfig, $facetCode) {
        // get the limits from the client if existing otherwize get the min and max from the database
        // this is need to match the client handling of the intervals in facet.range.js
        // use the limits to define the size of the interval
        $limits = $this->getLowerUpperLimit($conn, $facetConfig, $facetCode);
        $min_value = $limits["lower"];
        $max_value = $limits["upper"];
        $interval_count = 120; // should it be lower maybe??? when there is no data
        $interval = floor(($limits["upper"] - $limits["lower"]) / $interval_count);
        if ($interval <= 0) {
            $interval = 1;
        }
        // derive the interval as sql-query although it is not using a table...only a long sql with select the values of the interval
        $q1 = $this->getRangeQuery($interval, $min_value, $max_value, $interval_count);
        return [ $interval, $q1 ];
    }

    protected function getFacetCategoryCount($conn, $facetCode, $facetConfig, $interval_query)
    {
        return RangeFacetCounter::get_range_counts($conn, $facetCode, $facetConfig, $interval_query);
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

class DiscreteFacetContentLoader extends FacetContentLoader {

    function getTextFilterClause($facetConfig, $query_column_name)
    {
        global $filter_by_text;
        $find_str = trim($facetConfig["facet_collection"][$facetConfig["requested_facet"]]["facet_text_search"]);
        if ($find_str == "undefined") {
            $find_str = "%";
        }
        return (!empty($find_str) && $filter_by_text == true) ? " and " . $query_column_name . " ILIKE '" . $find_str . "' " : "";
    }

    protected function compileIntervalQuery($conn, $facetConfig, $facetCode)
    {
        global $facet_definition;

        $facetCode  = $facetConfig["requested_facet"];
        $facetCodes = FacetConfig::getKeysOfActiveFacets($facetConfig);
        
        $query = QueryBuildService::compileQuery($facetConfig, $facetCode, $data_tables, $facetCodes);

        $query_column_name = $facet_definition[$facetCode]["name_column"];
        $query_column = $facet_definition[$facetCode]["id_column"];
        $query_joins = $query["joins"];
        $sort_column = $facet_definition[$facetCode]["sort_column"];
        $sort_order = $facet_definition[$facetCode]["sort_order"];
        $find_cond = $this->getTextFilterClause($facetConfig, $query_column_name);
        $tables = $query["tables"];
        $where_clause = (trim($query["where"]) != '')  ?  " and " . $query["where"] : "";
        $group_by_columns = (!empty($sort_column) ? "$sort_column, " : "") . "$query_column, $query_column_name ";
        $sort_clause = (!empty($sort_column)) ? "order by $sort_column $sort_order" : "";

        $q1 =<<<EOT
            select $query_column as id , $query_column_name as name
            from $tables $query_joins
            where 1 = 1
              $find_cond
              $where_clause
            group by $group_by_columns
            $sort_clause
EOT;

        return [ 1, $q1 ];
    }

    protected function getFacetCategoryCount($conn, $facetCode, $facetConfig, $interval_query)
    {
        return DiscreteFacetCounter::get_discrete_counts($conn, $facetCode, $facetConfig, NULL);
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

class FacetContentService {

    public static function load($conn, $facetConfig)
    {
        global $facet_content_loaders, $facet_definition;
        $facetCode  = $facetConfig["requested_facet"];
        $facetType = $facet_definition[$facetCode]["facet_type"];

        $cacheId = CacheIdGenerator::computeFacetContentCacheId($facetConfig);
        if (!($facetContent = CacheHelper::get_facet_content($cacheId))) {
            $facetContent = $facet_content_loaders[$facetType]->get_facet_content($conn, $facetConfig);
            CacheHelper::put_facet_content($cacheId, $facetContent);
        }
        return $facetContent;
    }

}

?>