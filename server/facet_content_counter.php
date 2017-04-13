<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once __DIR__ . '/connection_helper.php';
require_once __DIR__ . '/facet_config.php';
require_once __DIR__ . '/config/environment.php';
require_once(__DIR__ . '/query_builder.php');
require_once __DIR__ . "/lib/SqlFormatter.php";

class RangeFacetCounter {

    public static function getQuery($facetsConfig, $facetCode, $intervalQuery)
    {
        global $direct_count_table, $direct_count_column;
        $facet = FacetRegistry::getDefinition($facetCode);
        $query = QueryBuildService::compileQuery2($facetsConfig, $facetCode, [ $facet->table, $direct_count_table ]);
        $sql = "
            SELECT lower, upper, category, count(category) AS direct_count
            FROM (
                SELECT COALESCE(lower||' => '||upper, 'data missing') AS category, group_column, lower,upper
                FROM  (
                    SELECT lower, upper, $direct_count_column AS group_column
                    FROM {$query->sql_table}
                    LEFT JOIN ( $intervalQuery ) AS temp_interval
                      ON {$facet->id_column}::integer >= lower
                     AND {$facet->id_column}::integer < upper
                         {$query->sql_joins}
                    {$query->sql_where2}
                    GROUP BY lower, upper, $direct_count_column
                    ORDER BY lower) AS x
                GROUP by lower, upper, group_column) AS y
            WHERE lower is not null
            AND upper is not null
            GROUP BY lower, upper, category
            ORDER BY lower, upper";

        return $sql;
    }

    public static function getCount($facetCode, $facetsConfig, $intervalQuery)
    {
        $sql = self::getQuery($facetsConfig, $facetCode, $intervalQuery);
        $cursor = ConnectionHelper::execute($sql);
        $categoryBounds = [];
        while ($row = pg_fetch_assoc($cursor)) {
            $categoryBounds[$row['category']] = $row["direct_count"];
        }
        return [ "list" => $categoryBounds, "sql" => SqlFormatter::format($sql, false) ];
    }
}

class DiscreteFacetCounter {

    private static function collectExtraTables($facetsConfig, $targetFacet, $countFacet)
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

    private static function getQuery($facetsConfig, $countCode, $aggType = "count")
    {
        $targetCode = $facetsConfig->targetCode ?: $countCode;
        $targetFacet = FacetRegistry::getDefinition($targetCode);
        $countFacet = FacetRegistry::getDefinition($countCode);
        $extraTables = self::collectExtraTables($facetsConfig, $targetFacet, $countFacet);
        $facetCodes = ArrayHelper::array_insert_before_existing($facetsConfig->getFacetCodes(), $targetCode, $countCode);
        
        $query = QueryBuildService::compileQuery($facetsConfig, $countCode, $extraTables, $facetCodes);

        $sql = "
            SELECT category, {$aggType}(summarize_term) AS direct_count
            FROM (
                SELECT {$targetFacet->id_column} AS category, {$countFacet->id_column} AS summarize_term
                FROM {$query->sql_table}
                     {$query->sql_joins}
                WHERE 1 = 1 {$query->sql_where2}
                GROUP BY {$countFacet->id_column}, {$targetFacet->id_column}
            ) AS x
            GROUP BY category;
        ";
        return $sql;
    }

    //***********************************************************************************************************************************************************************
    /*
    Function: getCount
    Arguments:
    * payload (interval for range facets, not used for discrete facets)
    Returns:
    associative array with counts, the keys are the category i.e the unique id of the row
    */

    public static function getCount($facetCode, $facetsConfig, $payload)
    {
        $facet = FacetRegistry::getDefinition($facetCode);
        $countCode = $facet->count_facet ?? "result_facet";
        $aggType = $facet->summarize_type ?? "count";
        $sql = self::getQuery($facetsConfig, $countCode, $aggType);
        $rs = ConnectionHelper::execute($sql);
        $histogram = [];
        while ($row = pg_fetch_assoc($rs)) {
            $category = $row["category"];
            $histogram["$category"] = $row["direct_count"];
        }
        return [ "list" => $histogram, "sql" => SqlFormatter::format($sql, false) ];
    }
}

class DiscreteMinMaxFacetCounter {

    public static function compute_max_min()
    {
        $facet_sqls = [];
        foreach (FacetRegistry::getDefinitions() as $facetCode => $facet) {
            if ($facet->facet_type != "range")
                continue;
            $query = QueryBuildService::compileQuery(NULL, $facetCode, [ $facet->table ], [ $facetCode ]);
            $facet_sqls[] = self::templateSQL($query, $facet, $facetCode);
        }
        $sql = implode("\nUNION\n", $facet_sqls);
        if (empty($sql)) {
            return [];
        }
        $facet_range = [];
        $rs = ConnectionHelper::execute($sql);
        while ($row = pg_fetch_assoc($rs)) {
            $facet_range["{$row['facet_code']}"] = [ "max" => $row["max"], "min" => $row["min"]];
        }
        return $facet_range;
    }

    private static function templateSQL($query, $facet, $facetCode): string
    {
        $sql =
            "SELECT '$facetCode' AS facet_code, MAX({$facet->id_column}::real) AS max, MIN({$facet->id_column}::real) AS min \n" .
            "FROM {$facet->table} \n" .
                 "{$query->sql_joins} \n" .
            str_prefix("WHERE ", $facet->query_cond);
        return $sql;
    }
}
?>
