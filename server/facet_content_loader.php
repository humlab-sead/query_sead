<?php

require_once __DIR__ . '/facet_histogram_loader.php';
require_once __DIR__ . '/lib/utility.php';
require_once __DIR__ . '/query_builder.php';

class FacetContent {

    public $facetCode;
    public $requestType;
    public $startRow;
    public $rowCount;
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

    public function computeWindow()
    {
        $facetsConfig = $this->facetsConfig;
        if ($facetsConfig->targetFacet->isOfType("range")) {
            return [0, 250];
        }
        $offset = $facetsConfig->targetConfig->startRow;
        $limit = $facetsConfig->targetConfig->rowCount;
        if ($this->requestType == "populate_text_search") {
            $offset = ArrayHelper::findIndex($this->rows, $facetsConfig->targetConfig->textFilter);
            $offset = max(0, min($offset, $this->totalRowCount - 12));
        }
        return [$offset, $limit];
    }
}

class FacetContentLoader {

    protected function compileIntervalQuery($facetsConfig, $facetCode)
    {
        return [ NULL, NULL ];
    }

    public function get_facet_content($facetsConfig)
    {
        list($interval, $intervalQuery) = $this->compileIntervalQuery($facetsConfig, $facetsConfig->targetCode);
        $histogram = $this->getHistogramLoader()->load($facetsConfig->targetCode, $facetsConfig, $intervalQuery);
        $pickMatrix = $facetsConfig->collectUserPicks($facetsConfig->targetCode);
        // FIXME Extract to seperate function such as generateHistogramMetaData
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
        $query = QuerySetupService::setup($facetsConfig, $facetCode, $data_tables, $facetsConfig->getFacetCodes());
        $sql = FacetContentExtraRowInfoSqlQueryBuilder::compile($query, $facet);
        $values = ConnectionHelper::queryKeyedValues($sql, 'id', 'name');
        return $values ?? [];
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
        $sql = RangeLowerUpperSqlQueryBuilder::compile(NULL, $facet);
        $facet_range = ConnectionHelper::queryRow($sql);
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

    protected function compileIntervalQuery($facetsConfig, $facetCode, $interval_count=120)
    {
        $limits = $this->getLowerUpperLimit($facetsConfig, $facetCode);
        $interval = floor(($limits["upper"] - $limits["lower"]) / $interval_count);
        if ($interval <= 0) {
            $interval = 1;
        }
        $sql = RangeIntervalSqlQueryBuilder::compile($interval, $limits["lower"], $limits["upper"], $interval_count);
        return [ $interval, $sql ];
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

    protected function getHistogramLoader()
    {
        return new RangeFacetHistogramLoader();
    }
}

class DiscreteFacetContentLoader extends FacetContentLoader {

    protected function compileIntervalQuery($facetsConfig, $notused)
    {
        $query = QuerySetupService::setup($facetsConfig, $facetsConfig->targetCode, [], $facetsConfig->getFacetCodes());
        $sql = DiscreteContentSqlQueryBuilder::compile($query, $facetsConfig->targetFacet, $facetsConfig->getTargetTextFilter());
        return [ 1, $sql ];
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

    protected function getHistogramLoader()
    {
        return new DiscreteFacetHistogramLoader();
    }
}

?>