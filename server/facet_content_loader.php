<?php

require_once __DIR__ . '/category_distribution_loader.php';
require_once __DIR__ . '/lib/utility.php';
require_once __DIR__ . '/query_builder.php';

class FacetContent {

    public $facetsConfig;
    public $facetCode;
    public $requestType;
    public $totalRowCount;
    public $compiledDistribution;
    public $rawDistribution;

    public $startRow;
    public $rowCount;
    public $interval;
    public $intervalQuery;
    public $countOfSelections;
    public $pickMatrix;

    function __construct($facetsConfig, $compiledDistribution, $rawDistribution, $pickMatrix, $interval, $intervalQuery)
    {
        $this->facetsConfig         = $facetsConfig;
        $this->facetCode            = $facetsConfig->targetCode;
        $this->compiledDistribution = $compiledDistribution ?? [];
        $this->rawDistribution      = $rawDistribution ?? [];                   // category-count from DB as a key-value list
        $this->totalRowCount        = count($this->compiledDistribution);
        $this->requestType          = $facetsConfig->requestType; 
        $this->startRow             = $facetsConfig->targetConfig->startRow;
        $this->rowCount             = $facetsConfig->targetConfig->rowCount;  
        $this->interval             = $interval;
        $this->intervalQuery        = $intervalQuery;
        $this->countOfSelections    = "";                                       // FIXME or remove (sent to client)
        $this->pickMatrix           = $pickMatrix ?? [];
    }

    public function getPage()
    {
        $facetsConfig = $this->facetsConfig;
        if ($facetsConfig->targetFacet->isOfType("range")) {
            return [0, 250];
        }
        list($offset, $size) = $facetsConfig->targetConfig->getPage();
        if ($this->requestType == "populate_text_search") {
            $offset = ArrayHelper::findIndex($this->compiledDistribution, $facetsConfig->targetConfig->textFilter);
            $offset = max(0, min($offset, $this->totalRowCount - 12));
        }
        return [$offset, $size];
    }
}

class FacetContentLoader {

    public function load($facetsConfig)
    {
        list($interval, $intervalQuery) = $this->compileIntervalQuery($facetsConfig, $facetsConfig->targetCode);

        //$extraCategoryInfo = $this->getExtraCategoryInfo($facetsConfig, $facet->extra_row_info_facet);
        $rawDistribution = $this->getRawDistribution($facetsConfig, $intervalQuery);
        $compiledDistribution = $this->compileDistribution($rawDistribution, $intervalQuery, $extraCategoryInfo);
        $pickMatrix = $facetsConfig->collectUserPicks($facetsConfig->targetCode);

        $facetContent = new FacetContent($facetsConfig, $compiledDistribution, $rawDistribution, $pickMatrix, $interval, $intervalQuery);
        return $facetContent;
    }

    protected function compileIntervalQuery($facetsConfig, $facetCode)
    {
        return [ NULL, NULL ];
    }

    private function getRawDistribution($facetsConfig, $intervalQuery)
    {
        $loader = CategoryDistributionLoader::create($facetsConfig->targetFacet->facet_type);
        $rawDistribution = $loader->load($facetsConfig->targetCode, $facetsConfig, $intervalQuery);
        return $rawDistribution;
    }

    protected function compileDistribution($rawDistribution, $intervalQuery, $extraCategoryInfo)
    {
        $compiledDistribution = [];
        $cursor = ConnectionHelper::query($intervalQuery);
        $distribution = $rawDistribution["list"];
        while ($row = pg_fetch_assoc($cursor)) {
            $id = $row["id"];
            $extra = ""; //array_key_exists($id, $extraCategoryInfo) ? $extraCategoryInfo[$id] : "";
            $compiledDistribution[] = [
                'values'         => $this->getCategoryItemValue($row),
                'display_name'   => $this->getCategoryItemDisplayName($row, $extra),
                'name'           => $this->getCategoryItemName($row, $extra),
                'category_count' => $distribution[$row["id"]] ?? "0"
            ];
        }
        return $compiledDistribution;        
    }

    protected function getCategoryItemValue($row)
    {
        return NULL;
    }

    protected function getCategoryItemName($row, $extra)
    {
        return $row["name"] . (!empty($extra) ? " ($extra)" : "");
    }

    protected function getCategoryItemDisplayName($row, $extra)
    {
        return $this->getCategoryItemName($row, $extra);
    }

    protected function getExtraCategoryInfo($facetsConfig)
    {
        return [];
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

    protected function getExtraCategoryInfo($facetsConfig)
    {
        $facet = $facetsConfig->targetFacet->extra_row_info_facet;
        if (!isset($facet))
            return [];
        $query = QuerySetupService::setup($facetsConfig, $facet->facet_code, [], $facetsConfig->getFacetCodes());
        $sql = FacetContentExtraRowInfoSqlQueryBuilder::compile($query, $facet);
        $values = ConnectionHelper::queryKeyedValues($sql, 'id', 'name') ?? [];
        return $values;
    }
}

class RangeFacetContentLoader extends FacetContentLoader {

    private function getLowerUpperBound($config)
    {
        $bounds = $config->getPickedLowerUpperBounds();          // Get client picked bound if exists...
        if (count($bounds) != 2) {
            $bounds = $config->getStorageLowerUpperBounds();     // ...else fetch from database
        }
        return [ $bounds["lower"],  $bounds["upper"] ];
    }

    protected function compileIntervalQuery($facetsConfig, $facetCode, $interval_count=120)
    {
        list($lower, $upper) = $this->getLowerUpperBound($facetsConfig->getConfig($facetCode));
        $interval = floor(($upper - $lower) / $interval_count);
        if ($interval <= 0) {
            $interval = 1;
        }
        $sql = RangeIntervalSqlQueryBuilder::compile($interval, $lower, $upper, $interval_count);
        return [ $interval, $sql ];
    }

    protected function getCategoryItemValue($row)
    {
        return [ "lower" => $row["lower"], "upper" => $row["upper"] ];
    }

    protected function getCategoryItemName($row, $extra)
    {
        return "{$row['lower']} to {$row['upper']}";
    }
}

?>