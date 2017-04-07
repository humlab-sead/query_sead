<?php

require_once(__DIR__ . '/facet_content_counter.php');
require_once(__DIR__ . '/cache_helper.php');
require_once __DIR__ . '/lib/utility.php';
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
        $facetCode = $facetConfig["requested_facet"];
        // $facet = FacetRegistry::getDefinition($facetCode);

        // compute the interval for a number of histogram items if it is defined as range facet
        list($interval, $interval_query) = $this->compileIntervalQuery($conn, $facetConfig, $facetCode);

        $histogram = $this->getFacetCategoryCount($conn, $facetCode, $facetConfig, $interval_query);

        $cursor = ConnectionHelper::query($conn, $interval_query);

        // FIXME! THIS CAN NOT WORK! SEE USE BELOW!
        // add extra information to a facet
        // if (isset($facet["extra_row_info_facet"])) {
        //     $extra_row_info = $this->getExtraRowInfo($facet["extra_row_info_facet"], $conn, $facetConfig);
        // }

        // FIXME: Move this to API!
        // $count_of_selections = "";
        // if ($count_of_selections > 0) {
        //     $matrix = FacetConfig::collectUserPicks($facetConfig, $facetCode);
        //     $tooltip_text = FacetPicksSerializer::toHTML($matrix);
        // }
        $tooltip_text = "";
        $report_text = " Current filter   <BR>  ". $tooltip_text."  " . SqlFormatter::format($interval_query, false) . "  ; \n  " . ($histogram["sql"] ?? "") . " ;\n";

        $row_counter = 0;
        $row_data = [];
        while ($row = pg_fetch_assoc($cursor)) {
            $report_text .= $this->getCategoryItemReport($row);
            $row_data[] = [
                'values' => $this->getCategoryItemValue($row),
                'name' => $this->getCategoryItemName($row /*, $extra_row_info */),
                'direct_counts' => $histogram["list"][$row["id"]] ?? "0"
            ];
            $row_counter++;
        }
        $facet_contents = [
            "$facetCode" => [
                'f_code' => $facetCode,
                'range_interval' => $interval,
                'f_action' => $facetConfig['f_action'][1],
                'start_row' => $facetConfig[$facetCode]['facet_start_row'],
                'rows_num' => $facetConfig[$facetCode]['facet_number_of_rows'],
                'total_number_of_rows' => $row_counter,
                'report' => $report_text,
                'report_html' => $tooltip_text,
                'count_of_selections' => "", // count_of_selections
                'report_xml' => "",
                'rows' => $row_data
            ]
        ];
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
//         $facet = FacetRegistry::getDefinition($facetCode);
//         $query_column = $facet["id_column"];
//         $facetCodes = FacetConfig::getCodesOfActiveFacets($facetConfig);
//         $query = QueryBuildService::compileQuery($facetConfig, $facetCode, $data_tables, $facetCodes);
//         $query_column_name = $facet["name_column"];
//         $sort_column = $facet["sort_column"];
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
    private function computeRangeLowerUpper($conn, $facetCode)
    {
        $facet = FacetRegistry::getDefinition($facetCode);
        $query_column = $facet["id_column"];
        $query_table = $facet["table"];
        $q = "select max($query_column) as max, min($query_column) as min from $query_table";
        $row = ConnectionHelper::queryRow($conn, $q);
        $facet_range = ["upper" => $row["max"], "lower" => $row["min"]];
        return $facet_range;
    }

    /*
    function: getLowerUpperLimit
    Gets the lower and limits in range-filter so the correct intervals can be computed.
    Uses the clients setting if exists, otherwise get it from the database.
    */
    private function getLowerUpperLimit($conn, $facetConfig, $facetCode)
    {
        $pickGroups = FacetConfig::getUserPickGroups($facetConfig);
        $limits = [];
        // FIXME: ROGER Criteria negated, must have been a bugg??? Was "if (!isset($pickGroups[$facetCode])) ..."
        if (array_key_exists($facetCode, $pickGroups)) {
            // FIXME: ROGER this cannot work...??
            foreach ($pickGroups[$facetCode] as $groups) {
                foreach ($groups as $group) {
                    foreach ($group as $item) {
                        $item = (array)$item;
                        $limits[$item["selection_type"]] = $item["selection_value"];
                    }
                }
            }
        } else {
            // If the limits are not set in the facet_xml from the client then use the min and max values from the database
            $limits = $this->computeRangeLowerUpper($conn, $facetCode);
        }
        return $limits;
    }

    /*
    function: getRangeQuery
    get the sql-query for facet with interval data by computing a sql-query by adding the interval number into a sql-text
    */

    private function getRangeQuery($interval, $min_value, $max_value, $interval_count)
    {
        $pieces = [];
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

    function getTextFilterClause($facetConfig, $column_name)
    {
        if (ConfigRegistry::getFilterByText())
            return "";
        $term = trim($facetConfig["facet_collection"][$facetConfig["requested_facet"]]["facet_text_search"]);
        if ($term == "undefined") {
            return "";
        }
        return empty($term) ? "" : " AND $column_name ILIKE '$term' ";
    }

    /**
     * @param $conn
     * @param $facetConfig
     * @param $facetCode
     * @return array
     */
    protected function compileIntervalQuery($conn, $facetConfig, $facetCode)
    {
        $facetCode  = $facetConfig["requested_facet"];
        $facetCodes = FacetConfig::getCodesOfActiveFacets($facetConfig);
        $facet = FacetRegistry::getDefinition($facetCode);
        
        $query = QueryBuildService::compileQuery($facetConfig, $facetCode, [], $facetCodes);

        $text_criteria = $this->getTextFilterClause($facetConfig, $facet['name_column']);
        $where_clause = str_prefix("AND ", $query["where"]);
        $sort_clause = empty($facet['sort_column']) ? "" : ", {$facet['sort_column']} ORDER BY {$facet['sort_column']} {$facet['sort_order']}";

        $q1 = "
            SELECT {$facet['id_column']} AS id, {$facet['name_column']} AS name
            FROM {$query['tables']}
                 {$query['joins']}
            WHERE 1 = 1
              $text_criteria
              $where_clause
            GROUP BY {$facet['id_column']}, {$facet['name_column']} $sort_clause";

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

$facet_content_loaders = array(
    "discrete" => new DiscreteFacetContentLoader(),
    "range" => new RangeFacetContentLoader()
);

class FacetContentService {

    public static function load($conn, $facetConfig)
    {
        global $facet_content_loaders;
        $facetCode = $facetConfig["requested_facet"];
        $facet = FacetRegistry::getDefinition($facetCode);
        $facetType = $facet["facet_type"];
        $cacheId = CacheIdGenerator::computeFacetContentCacheId($facetConfig);
        if (!($facetContent = CacheHelper::get_facet_content($cacheId))) {
            $facetContent = $facet_content_loaders[$facetType]->get_facet_content($conn, $facetConfig);
            CacheHelper::put_facet_content($cacheId, $facetContent);
        }
        return $facetContent;
    }

}

?>