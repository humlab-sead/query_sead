<?php

require_once(__DIR__ . '/facet_content_counter.php');
require_once(__DIR__ . '/cache_helper.php');
require_once __DIR__ . '/lib/utility.php';
require_once(__DIR__ . '/query_builder.php');

class FacetContent {

    public $facetCode;
    public $requestType;    // = $facetsConfig->requestType
    public $startRow;       // = $facetsConfig->requestType
    public $rowCount;      // = $facetsConfig->rowCount
    public $interval;
    public $intervalQuery;
    public $facetsConfig;
    public $totalRowCount;
    public $rows;
    public $report;
    public $reportHtml;
    public $reportXml;
    public $countOfSelections;
    public $histogram;
    public $pickMatrix;

    function __construct($facetsConfig, $rows,  $histogram, $pickMatrix, $interval, $intervalQuery)
    {
        $this->facetCode = $facetsConfig->targetCode;
        $this->requestType = $facetsConfig->requestType; 
        $this->startRow = $facetsConfig->targetConfig->startRow;
        $this->rowCount = $facetsConfig->targetConfig->rowCount;  
        $this->interval = $interval;
        $this->intervalQuery = $intervalQuery;
        $this->facetsConfig = $facetsConfig;
        $this->rows = $rows ?? [];
        $this->totalRowCount = count($this->rows);
        $this->report = ""; // FIXME: compute in serialize!
        $this->reportHtml = "";// FIXME: compute in client!
        $this->reportXml = "";// FIXME: compute in serialize!
        $this->countOfSelections = ""; // FIXME or remove
        $this->histogram = $histogram ?? [];
        $this->pickMatrix = $pickMatrix ?? [];
    }
}

class FacetContentLoader {

    protected function compileIntervalQuery($facetsConfig, $facetCode)
    {
        return [ NULL, NULL ];
    }

    protected function getFacetCategoryCount($facetCode, $facetsConfig, $interval_query)
    {
        return NULL;
    }

    public function get_facet_content($facetsConfig)
    {
        list($interval, $intervalQuery) = $this->compileIntervalQuery($facetsConfig, $facetsConfig->targetCode);
        $histogram = $this->getFacetCategoryCount($facetsConfig->targetCode, $facetsConfig, $intervalQuery);
        $pickMatrix = $facetsConfig->collectUserPicks($facetsConfig->targetCode);
        $rows = [];
        $cursor = ConnectionHelper::query($intervalQuery);
        while ($row = pg_fetch_assoc($cursor)) {
            $report_text .= $this->getCategoryItemReport($row);
            $rows[] = [
                'values' => $this->getCategoryItemValue($row),
                'name' => $this->getCategoryItemName($row /*, $extra_row_info */),
                'direct_counts' => $histogram["list"][$row["id"]] ?? "0"
            ];
        }
        $facetContent = new FacetContent($facetsConfig, $rows,  $histogram, $pickMatrix, $interval, $intervalQuery);
        return $facetContent;

        // FIXME! THIS CAN NOT WORK! SEE USE BELOW!
        // add extra information to a facet
        // if (isset($facet->extra_row_info_facet)) {
        //     $extra_row_info = $this->getExtraRowInfo($facetsConfig, $facet->extra_row_info_facet);
        // }
        // FIXME Move to serialize!
        // $tooltip_text = FacetPicksSerializer::toHTML($content['pick_matrix']);
        // $report_text = " Current filter   <BR>  ". $tooltip_text . "  " .
        //    SqlFormatter::format($content['interval_query'], false) . "  ; \n  " .
        //   ($content['interval_query']['sql'] ?? "") . " ;\n";

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
    private function getExtraRowInfo($facetsConfig, $facetCode)
    {
        $facet = FacetRegistry::getDefinition($facetCode);
        $query = QueryBuildService::compileQuery($facetsConfig, $facetCode, $data_tables, $facetsConfig->getFacetCodes());
        $q1 = <<<EOS
            SELECT DISTINCT id, name
            FROM (
                SELECT {$facet->id_column} AS id, Coalesce({$facet->name_column},'No value') AS name, $sort_column AS sort_column
                FROM {$query->sql_table} 
                     {$query->sql_joins}
                WHERE 1 = 1
                  {$query->sql_where2}
                GROUP BY name, id, sort_column
                ORDER BY {$facet->sort_column}
            ) AS tmp
EOS;
        $rs = ConnectionHelper::execute($q1);
        while ($row = pg_fetch_assoc($rs)) {
            $extra_row_info[$row["id"]] = $row["name"];
        }
        return $extra_row_info ?? [];
    }
}

class RangeFacetContentLoader extends FacetContentLoader {

   // compute max and min for range facet
    /*
    Function: computeRangeLowerUpper
    Get the min and max values of filter-variable from the database table.
    */
    private function computeRangeLowerUpper($facetCode)
    {
        $facet = FacetRegistry::getDefinition($facetCode);
        $q = "SELECT MAX({$facet->id_column}) AS max, MIN({$facet->id_column}) AS min " .
             "FROM {$facet->table}";
        $row = ConnectionHelper::queryRow($q);
        $facet_range = ["upper" => $row["max"], "lower" => $row["min"]];
        return $facet_range;
    }

    /*
    function: getLowerUpperLimit
    Gets the lower and limits in range-filter so correct intervals can be computed.
    Uses the clients setting if exists, otherwise gets it from the database.
    */
    private function getLowerUpperLimit($facetsConfig, $facetCode)
    {
        $limits = [];
        foreach ($facetsConfig->getConfig($facetCode)->picks as $pick) {
            $limits[$pick->type] = $pick->value;
        }
        if (count($limits) != 2) {
            $limits = $this->computeRangeLowerUpper($facetCode);
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
        $lower = $min_value;
        for ($i = 0; $i <= $interval_count && $lower <= $max_value; $i++) {
            $upper = $lower + $interval;
            $pieces[] = "($lower, $upper, '$lower => $upper', '')";
            $lower += $interval;
        }
        return "SELECT lower, upper, id, name FROM (VALUES " . implode("\n,", $pieces) . ") AS X(lower, upper, id, name)";
    }

    protected function compileIntervalQuery($facetsConfig, $facetCode)
    {
        $interval_count = 120;
        $limits = $this->getLowerUpperLimit($facetsConfig, $facetCode);
        $interval = floor(($limits["upper"] - $limits["lower"]) / $interval_count);
        if ($interval <= 0) {
            $interval = 1;
        }
        $q1 = $this->getRangeQuery($interval, $limits["lower"], $limits["upper"], $interval_count);
        return [ $interval, $q1 ];
    }

    protected function getFacetCategoryCount($facetCode, $facetsConfig, $interval_query)
    {
        return RangeFacetCounter::getCount($facetCode, $facetsConfig, $interval_query);
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

    protected function compileIntervalQuery($facetsConfig, $notused)
    {
        $query = QueryBuildService::compileQuery($facetsConfig, $facetsConfig->targetCode, [], $facetsConfig->getFacetCodes());
        $sql = $this->compileSQL($facetsConfig, $facetsConfig->targetFacet, $query);
        return [ 1, $sql ];
    }

    protected function getFacetCategoryCount($facetCode, $facetsConfig, $notused)
    {
        return DiscreteFacetCounter::getCount($facetCode, $facetsConfig, NULL);
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

    protected function compileSQL($facetsConfig, $facet, $query): string
    {
        $text_criteria = $this->getTextFilterClause($facetsConfig, $facet->name_column);
        $sort_clause = str_prefix(", {$facet->sort_column} ", $facet->sql_sort_clause);
        $sql = "
            SELECT {$facet->id_column} AS id, {$facet->name_column} AS name
            FROM {$query->sql_table}
                 {$query->sql_joins}
            WHERE 1 = 1
              {$text_criteria}
              {$query->sql_where2}
            GROUP BY {$facet->id_column}, {$facet->name_column}
            {$sort_clause}";
        return $sql;
    }

    function getTextFilterClause($facetsConfig, $column_name)
    {
        $filter = trim($facetsConfig->targetConfig->textFilter);
        return empty($filter) ? "" : " AND $column_name ILIKE '$filter' ";
    }
}

// FIXME: Move to API Service
$facet_content_loaders = array(
    "discrete" => new DiscreteFacetContentLoader(),
    "range" => new RangeFacetContentLoader()
);

class FacetContentService {

    public static function load($facetsConfig)
    {
        global $facet_content_loaders;
        $cacheId = $facetsConfig->getCacheId();
        if (!($facetContent = CacheHelper::get_facet_content($cacheId))) {
            $loader = $facet_content_loaders[$facetsConfig->targetFacet->facet_type];
            $facetContent = $loader->get_facet_content($facetsConfig);
            CacheHelper::put_facet_content($cacheId, $facetContent);
        }
        return $facetContent;
    }
}
?>