<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once __DIR__ . '/connection_helper.php';
require_once __DIR__ . '/facet_config.php';
require_once __DIR__ . '/config/environment.php';
require_once(__DIR__ . '/query_builder.php');
require_once(__DIR__ . '/sql_query_builder.php');
require_once __DIR__ . "/lib/SqlFormatter.php";

class CategoryDistributionLoader {

    public function load($facetCode, $facetsConfig, $intervalQuery=NULL)
    {
        $facet = FacetRegistry::getDefinition($facetCode);
        $sql = $this->compileSql($facet, $facetsConfig, $intervalQuery);
        $data = ConnectionHelper::queryKeyedValues($sql, 'category', 'direct_count');
        return [ "list" => $data, "sql" => SqlFormatter::format($sql, false) ];
    }

    protected function compileSql($facet, $facetsConfig, $intervalQuery)
    {
        return "";
    }

    private static $loaders = [
        "discrete"  => "DiscreteCategoryDistributionLoader",
        "range"     => "RangeCategoryDistributionLoader"
    ];
    public static function create($facetType)
    {
        return array_key_exists($facetType, self::$loaders) ? new self::$loaders[$facetType]() : NULL;
    }
}

class RangeCategoryDistributionLoader extends CategoryDistributionLoader {

    protected function compileSql($facet, $facetsConfig, $intervalQuery)
    {
        global $direct_count_table, $direct_count_column;
        $query = QuerySetupService::setup2($facetsConfig, $facet->facet_code, [ $facet->table, $direct_count_table ]);
        $sql = RangeCounterSqlQueryBuilder::compile($query, $facet, $intervalQuery, $direct_count_column);
        return $sql;
    }
}

class DiscreteCategoryDistributionLoader extends CategoryDistributionLoader {

    protected function compileSql($facet, $facetsConfig, $payload)
    {
        $countCode   = $facet->count_facet ?: "result_facet";
        $aggType     = $facet->summarize_type ?: "count";
        $targetCode  = $facetsConfig->targetCode ?: $countCode;
        $targetFacet = FacetRegistry::getDefinition($targetCode);
        $countFacet  = FacetRegistry::getDefinition($countCode);
        $extraTables = self::collectExtraTables($facetsConfig, $targetFacet, $countFacet);
        $facetCodes  = ArrayHelper::array_insert_before_existing($facetsConfig->getFacetCodes(), $targetCode, $countCode);
        $query       = QuerySetupService::setup($facetsConfig, $countCode, $extraTables, $facetCodes);
        $sql         = DiscreteCounterSqlQueryBuilder::compile($query, $targetFacet, $countFacet, $aggType);
        return $sql;
    }

    private function collectExtraTables($facetsConfig, $targetFacet, $countFacet)
    {
        $extraTables = $targetFacet->query_cond_table ?: [];
        if (!empty($facetsConfig->targetCode)) {
            $extraTables[] = $targetFacet->table_or_alias;
        }
        if ($countFacet->facet_code != $targetFacet->facet_code) {
            $extraTables[] = $countFacet->table;
        }
        return $extraTables;
    }
}

class RangeCategoryBoundsLoader {

    public static function load()
    {
        $sqls = [];
        foreach (FacetRegistry::getOfType('range') as $facetCode => $facet) {
            $query = QuerySetupService::setup(NULL, $facetCode, [ $facet->table ], [ $facetCode ]);
            $sqls[] = RangeCategoryBoundSqlQueryBuilder::compile($query, $facet, $facetCode);
        }
        $sql = implode("\nUNION\n", $sqls);
        if (empty($sql)) {
            return [];
        }
        $categoryBounds = ConnectionHelper::queryKeyedRows($sql, 'facet_code');
        return $categoryBounds;
    }
}
?>
