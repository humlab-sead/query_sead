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
        global $facet_definition, $direct_count_table, $direct_count_column;
        if (empty($direct_count_column)) {
            return NULL;
        }        
        $direct_counts = [];
        $query_table = $facet_definition[$facetCode]["table"];
        $data_tables[] = $query_table;
        $data_tables[] = $direct_count_table;
        
        $activeCodes = FacetConfig::getCodesOfActiveFacets($facetConfig);
        
        //check if f_code exist in list, if not add it. since counting can be done also in result area and then using "abstract facet" that are not normally part of the list
        if (!in_array($facetCode, $activeCodes)) {
            $activeCodes[] = $facetCode;
        }
        
        // use the id's to compute direct resource counts
        // filter is not being used be needed as parameter
        $query = QueryBuildService::compileQuery($facetConfig, $facetCode, $data_tables, $activeCodes);
        $query_column = $facet_definition[$facetCode]["id_column"] . "::integer";
        $query_tables = $query["tables"];
        $where_clause = $query["where"] != '' ? " and  " . $query["where"] . " " : "";
        $extra_join = $query["joins"] != "" ?  "  " . $query["joins"] . "  " : "";

        $q = "
            SELECT lower, upper, category, count(category) AS direct_count
            FROM (
                SELECT COALESCE(lower||'=>'||upper, 'data missing') AS category, group_column, lower,upper
                FROM  (
                    SELECT lower, upper, $direct_count_column AS group_column
                    FROM $query_tables
                    LEFT JOIN ( $q_interval ) AS temp_interval
                      ON $query_column >= lower
                     AND $query_column < upper
                    $extra_join
                    $where_clause
                    GROUP BY lower, upper, $direct_count_column
                    ORDER BY lower) AS x
                GROUP by lower, upper, group_column) AS y
            WHERE lower is not null
            AND upper is not null
            GROUP BY lower, upper, category
            ORDER BY lower, upper";

        $rs = ConnectionHelper::execute($conn, $q);

        while ($row = pg_fetch_assoc($rs)) {
            $category = $row['category'];
            $direct_counts["$category"] = $row["direct_count"];
        }
        
        return [ "list" => $direct_counts, "sql" => SqlFormatter::format($q, false) ];
    }
}

class DiscreteFacetCounter {

    /*
    function: get_discrete_count_query
    get sql-query for counting of discrete-facet
    */
    private static function get_discrete_count_query($countCode, $facetConfig, $summarize_type = "count")
    {
        $activeCodes = FacetConfig::getCodesOfActiveFacets($facetConfig);
        
        $requestedCode = $facetConfig["requested_facet"] ?: $countCode;
        $requestedFacet = FacetRegistry::getDefinition($requestedCode);
        $countFacet = FacetRegistry::getDefinition($countCode);

        $extraTables = array_values($requestedFacet["query_cond_table"] ?: []);
        if (!empty($facetConfig["requested_facet"])) {
            $extraTables[] = $requestedFacet["alias_table"] ?? $requestedFacet["table"];
        }
        if ($countCode != $requestedCode) {
            $extraTables[] = $countFacet["table"];
        }

        // $extraTables = array_merge(
        //     empty($facetConfig["requested_facet"]) ? [] : [ $requestedFacet["alias_table"] ?? $requestedFacet["table"] ],
        //     $countCode == $requestedCode           ? [] : [ $countFacet["table"] ],
        //     array_values($requestedFacet["query_cond_table"] ?: [])
        // );

        $activeCodes = ArrayHelper::array_insert_before_existing($activeCodes, $requestedCode, $countCode);
        
        $query = QueryBuildService::compileQuery($facetConfig, $countCode, $extraTables, $activeCodes);

        $count_column = $countFacet["id_column"];
        $requested_facet_column = $requestedFacet["id_column"];
        $query_tables = $query["tables"];
        $extra_join = ($query["joins"] != "") ? $query["joins"] . "  " : "";
        $extra_where = ($query["where"] != '')  ? " and  " . $query["where"] . " " : "";

        $q = <<<EOT
SELECT category, $summarize_type(summarize_term) AS direct_count
FROM (
    SELECT $requested_facet_column AS category, $count_column AS summarize_term
    FROM $query_tables
    $extra_join
    WHERE 1 = 1
     $extra_where
    GROUP BY $count_column, $requested_facet_column
) AS x
GROUP BY category;
EOT;
        return $q;
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
        $q = self::get_discrete_count_query($countCode, $facetConfig, $summarize_type);
        $rs = ConnectionHelper::execute($conn, $q);
        $direct_counts = [];
        while ($row = pg_fetch_assoc($rs)) {
            $category = $row["category"];
            $direct_counts["$category"] = $row["direct_count"];
        }
        return [ "list" => $direct_counts, "sql" => SqlFormatter::format($q, false) ];
    }
}

class DiscreteMinMaxFacetCounter {
    //FIXME Move to service
    public static function compute_max_min($conn)
    {
        $facet_sqls = [];
        foreach (FacetRegistry::getDefinitions() as $facetCode => $facet) {
            if ($facet["facet_type"] != "range")
                continue;
            $data_tables[] = $facet['table'];
            $facetCodes[] = $facetCode;

            $query = QueryBuildService::compileQuery(NULL, $facetCode, $data_tables, $facetCodes);
            
            $extra_join = $query["joins"] ?? "";
            $where_clause = str_prefix("WHERE ", $facet["query_cond"]);

            $facet_sqls[] = 
                "SELECT '$facetCode' AS f_code, MAX({$facet['id_column']}::real) AS max, MIN({$facet['id_column']}::real) AS min \n" .
                "FROM {$facet['table']} \n" .
                    "$extra_join \n" .
                "$where_clause \n";
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
}
?>
