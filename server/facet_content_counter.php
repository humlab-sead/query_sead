<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once __DIR__ . '/connection_helper.php';
require_once __DIR__ . '/facet_config.php';
require_once(__DIR__ . '/query_builder.php');
require_once __DIR__ . "/lib/SqlFormatter.php";

class RangeFacetCounter {

    public static function get_range_counts($conn, $facetCode, $facetConfig, $q_interval)
    {
        global $facet_definition, $direct_count_table, $direct_count_column;
        if (empty($direct_count_column)) {
            return NULL;
        }        
        $direct_counts = array();
        $combined_list = array();
        $query_table = $facet_definition[$facetCode]["table"];
        $data_tables[] = $query_table;
        $data_tables[] = $direct_count_table;
        
        $f_list = FacetConfig::getKeysOfActiveFacets($facetConfig);
        
        //check if f_code exist in list, if not add it. since counting can be done also in result area and then using "abstract facet" that are not normally part of the list
        if (!in_array($facetCode, $f_list)) {
            $f_list[] = $facetCode;
        }
        
        // use the id's to compute direct resource counts
        // filter is not being used be needed as parameter
        $query = QueryBuildService::compileQuery($facetConfig, $facetCode, $data_tables, $f_list);
        $query_column = $facet_definition[$facetCode]["id_column"] . "::integer";
        $query_tables = $query["tables"];
        $where_clause = $query["where"] != '' ? " and  " . $query["where"] . " " : "";
        $extra_join = $query["joins"] != "" ?  "  " . $query["joins"] . "  " : "";

        $q =<<<EOX
            select lower, upper, facet_term, count(facet_term) as direct_count
            from (select  COALESCE(lower||'=>'||upper, 'data missing') as facet_term,group_column, lower,upper
                from  ( select lower,upper , $direct_count_column  as group_column
                        from $query_tables
                        left  join ( $q_interval ) as temp_interval
                            on $query_column >= lower
                        and $query_column < upper
                        $extra_join $where_clause
                        group by lower, upper ,$direct_count_column
                        order by lower) as tmp4
                group by lower, upper, group_column) as tmp3
            where lower is not null
            and upper is not null
            group by lower, upper, facet_term
            order by lower, upper
EOX;

        $rs = ConnectionHelper::execute($conn, $q);

        while ($row = pg_fetch_assoc($rs)) {
            $facet_term = $row["facet_term"];
            $direct_counts["$facet_term"] = $row["direct_count"];
        }
        
        $combined_list["list"] = $direct_counts;
        $combined_list["sql"] = SqlFormatter::format($q, false);
        return $combined_list;
    }
}

class DiscreteFacetCounter {

    //***************************************************************************************************************************************************
    /*
    * insert an item an array
    */
    private static function insert_item_before_search_item($f_list, $requested_facet, $insert_item)
    {
        if (!isset($f_list)) {
            $new_list[]=$insert_item;
            return $new_list;
        }
        foreach ($f_list as $key => $f_list_element) {
            if ($requested_facet==$f_list_element) {
                //insert count facet before
                $new_list[]=$insert_item;
            }
            $new_list[]=$f_list_element;
        }
        return $new_list;
    }

    /*
    function: get_discrete_count_query
    get sql-query for counting of discrete-facet
    */
    private static function get_discrete_count_query2($count_facet, $facetConfig, $summarize_type = "count")
    {
        global $facet_definition;
        $f_list = FacetConfig::getKeysOfActiveFacets($facetConfig);
        if (!empty($facetConfig["requested_facet"])) {
            $requested_facet=$facetConfig["requested_facet"];
            $extra_tables[]=isset($facet_definition[$requested_facet]["alias_table"]) ? $facet_definition[$requested_facet]["alias_table"]:    $facet_definition[$requested_facet]["table"];
        } else {
            $requested_facet=$count_facet;
        }
        
        $extra_tables[]=$facet_definition[$count_facet]["table"];
        if (!empty($facet_definition[$requested_facet]["query_cond_table"])) {
            foreach ($facet_definition[$requested_facet]["query_cond_table"] as $cond_table) {
                $extra_tables[] =$cond_table;
            }
        }
        
        $f_list = self::insert_item_before_search_item($f_list, $requested_facet, $count_facet);
        
        $query = QueryBuildService::compileQuery($facetConfig, $count_facet, $extra_tables, $f_list);

        $count_column = $facet_definition[$count_facet]["id_column"];
        $requested_facet_column = $facet_definition[$requested_facet]["id_column"];
        $query_tables = $query["tables"];
        $extra_join = ($query["joins"] != "") ? $query["joins"] . "  " : "";
        $extra_where = ($query["where"] != '')  ? " and  " . $query["where"] . " " : "";

        $q = <<<EOT

        select facet_term, $summarize_type(summarize_term) as direct_count
        from (
            select $requested_facet_column  as facet_term , $count_column as summarize_term
            from $query_tables
                $extra_join
            where 1 = 1
                $extra_where
            group by $count_column, $requested_facet_column
        ) as tmp_query
        group by facet_term;

EOT;
        return $q;
    }

    //***********************************************************************************************************************************************************************
    /*
    Function: get_discrete_counts
    Arguments:
    * table over which to do counting
    * column over which to do counting
    * payload (interval for range facets, not used for discrete facets)
    Returns:
    associative array with counts, the keys are the facet_term i.e the unique id of the row
    */

    public static function get_discrete_counts($conn, $facetCode, $facetConfig, $payload)
    {
        global $facet_definition, $direct_count_table, $direct_count_column;
        if (empty($direct_count_column)) {
            return NULL;
        }
        $direct_counts = array();
        $combined_list = array();
        $data_tables[] = $direct_count_table;
        $query_table = isset($facet_definition["alias_table"]) ? $facet_definition["alias_table"] : $facet_definition[$facetCode]["table"];
        $data_tables[] = $query_table;
        $f_list = FacetConfig::getKeysOfActiveFacets($facetConfig);
        //check if f_code exist in list, if not add it. since counting can be done also in result area and then using "abstract facet" that are not normally part of the list
        if (isset($f_list)) {
            if (!in_array($facetCode, $f_list)) {
                $f_list[] = $facetCode;
            }
        }
        // use the id's to compute direct resource counts
        // filter is not being used be needed as parameter
        $count_facet = $facet_definition[$facetCode]["count_facet"] ?? "result_facet";
        $summarize_type = $facet_definition[$facetCode]["summarize_type"] ?? "count";
        $q = self::get_discrete_count_query2($count_facet, $facetConfig, $summarize_type);
        $max_count = 0;
        $min_count = 99999999999999999;
        $rs = ConnectionHelper::execute($conn, $q);
        while ($row = pg_fetch_assoc($rs)) {
            $facet_term = $row["facet_term"];
            $max_count = max($max_count, $row["direct_count"]);
            $min_count = min($min_count, $row["direct_count"]);
            $direct_counts["$facet_term"] = $row["direct_count"];
        }

        $combined_list["list"] = $direct_counts;
        $combined_list["sql"] = $q;
        return $combined_list;
    }
}

?>
