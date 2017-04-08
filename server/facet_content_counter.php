<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once __DIR__ . '/connection_helper.php';
require_once __DIR__ . '/facet_config.php';
require_once __DIR__ . '/config/environment.php';
require_once(__DIR__ . '/query_builder.php');
require_once __DIR__ . "/lib/SqlFormatter.php";

class RangeFacetCounter {

    public static function get_range_counts($conn, $facetCode, $facetConfig, $q_interval)
    {
        global $direct_count_table, $direct_count_column;

        $facet = FacetRegistry::getDefinition($facetCode);
        $query = QueryBuildService::compileQuery2($facetConfig, $facetCode, [ $facet["table"], $direct_count_table ]);
        $where_clause = str_prefix("AND ", $query['where']);
        $sql = "
            SELECT lower, upper, category, count(category) AS direct_count
            FROM (
                SELECT COALESCE(lower||'=>'||upper, 'data missing') AS category, group_column, lower,upper
                FROM  (
                    SELECT lower, upper, $direct_count_column AS group_column
                    FROM {$query_tables}
                    LEFT JOIN ( $q_interval ) AS temp_interval
                      ON {$facet['id_column']}::integer >= lower
                     AND {$facet['id_column']}::integer < upper
                         {$query['joins']}
                    {$where_clause}
                    GROUP BY lower, upper, $direct_count_column
                    ORDER BY lower) AS x
                GROUP by lower, upper, group_column) AS y
            WHERE lower is not null
            AND upper is not null
            GROUP BY lower, upper, category
            ORDER BY lower, upper";

        $cursor = ConnectionHelper::execute($conn, $sql);
        $categoryBounds = [];
        while ($row = pg_fetch_assoc($cursor)) {
            $categoryBounds[$row['category']] = $row["direct_count"];
        }
        
        return [ "list" => $categoryBounds, "sql" => SqlFormatter::format($sql, false) ];
    }
}

class DiscreteFacetCounter {

    /*
    function: get_discrete_count_query
    get sql-query for counting of discrete-facet
    */
    private static function get_discrete_count_query($countCode, $facetConfig, $summarize_type = "count")
    {
        $requestedCode = $facetConfig["requested_facet"] ?: $countCode;
        $requestedFacet = FacetRegistry::getDefinition($requestedCode);
        $countFacet = FacetRegistry::getDefinition($countCode);
        $extraTables = array_values($requestedFacet["query_cond_table"] ?: []);
        if (!empty($facetConfig["requested_facet"])) {
            $extraTables[] = $requestedFacet["alias_table"] ?: $requestedFacet["table"];
        }
        if ($countCode != $requestedCode) {
            $extraTables[] = $countFacet["table"];
        }
        $activeCodes = FacetConfig::getCodesOfActiveFacets($facetConfig);
        $activeCodes = ArrayHelper::array_insert_before_existing($activeCodes, $requestedCode, $countCode);
        
        $query = QueryBuildService::compileQuery($facetConfig, $countCode, $extraTables, $activeCodes);

        $extra_where = str_prefix("AND ", $query['where']);
        $sql = "
            SELECT category, {$summarize_type}(summarize_term) AS direct_count
            FROM (
                SELECT {$requestedFacet['id_column']} AS category, {$countFacet['id_column']} AS summarize_term
                FROM {$query['tables']}
                     {$query['joins']}
                WHERE 1 = 1
                  {$extra_where}
                GROUP BY {$countFacet['id_column']}, {$requestedFacet['id_column']}
            ) AS x
            GROUP BY category;
        ";
        return $sql;
    }

    //***********************************************************************************************************************************************************************
    /*
    Function: get_discrete_counts
    Arguments:
    * payload (interval for range facets, not used for discrete facets)
    Returns:
    associative array with counts, the keys are the category i.e the unique id of the row
    */

    public static function get_discrete_counts($conn, $facetCode, $facetConfig, $payload)
    {
        $facet = FacetRegistry::getDefinition($facetCode);
        $countCode = $facet["count_facet"] ?? "result_facet";
        $summarize_type = $facet["summarize_type"] ?? "count";
        $sql = self::get_discrete_count_query($countCode, $facetConfig, $summarize_type);
        $rs = ConnectionHelper::execute($conn, $sql);
        $direct_counts = [];
        while ($row = pg_fetch_assoc($rs)) {
            $category = $row["category"];
            $direct_counts["$category"] = $row["direct_count"];
        }
        return [ "list" => $direct_counts, "sql" => SqlFormatter::format($sql, false) ];
    }
}

class DiscreteMinMaxFacetCounter {

    public static function compute_max_min($conn)
    {
        $facet_sqls = [];
        foreach (FacetRegistry::getDefinitions() as $facetCode => $facet) {

            if ($facet["facet_type"] != "range")
                continue;

            $query = QueryBuildService::compileQuery(NULL, $facetCode, [ $facet['table'] ], [ $facetCode ]);
            $facet_sqls[] = self::templateSQL($query, $facet, $facetCode);
        }

        $sql = implode("UNION\n", $facet_sqls);
        if (empty($sql)) {
            return [];
        }
        $facet_range = [];
        $rs = ConnectionHelper::execute($conn, $sql);
        while ($row = pg_fetch_assoc($rs)) {
            $facet_range[$row["f_code"]] = [ "max" => $row["max"], "min" => $row["min"]];
        }
        return $facet_range;
    }

    private static function templateSQL($query, $facet, $facetCode): string
    {
        $extra_join = $query["joins"] ?? "";
        $where_clause = str_prefix("WHERE ", $facet["query_cond"]);
        $sql =
            "SELECT '$facetCode' AS f_code, MAX({$facet['id_column']}::real) AS max, MIN({$facet['id_column']}::real) AS min \n" .
            "FROM {$facet['table']} \n" .
            "$extra_join \n" .
            "$where_clause \n";
        return $sql;
    }
}
?>
