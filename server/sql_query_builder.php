<?php

class SqlFieldCompiler
{
    protected static $compilers = NULL;

    public static function getCompiler($type)
    {
        return self::isFieldType($type) ? self::getCompilers()[$type] : NULL;
    }

    public function isFieldType($type)
    {
        return array_key_exists($type, self::getCompilers());
    }

    public static function isSingleItemType($type)
    {
        return self::isFieldType($type) && (get_class(self::getCompiler($type)) == "SqlFieldCompiler");
    }

    public static function isAggregateType($type)
    {
        return self::isFieldType($type) && !self::isSingleItemType($type);
    }

    public static function isSortType($type)
    {
        return $type == "sort_item";
    }

    public static function isGroupByType($type)
    {
        return self::isSingleItemType($type) || self::isSortType($type);
    }

    public static function getCompilers()
    {
        if (self::$compilers == NULL)
            self::$compilers = [
                "sum_item" => new SumFieldCompiler(),
                "count_item" => new CountFieldCompiler(),
                "avg_item" => new AvgFieldCompiler(),
                "text_agg_item" => new TextAggFieldCompiler(),
                "single_item" => new SqlFieldCompiler(),
                "link_item" => new SqlFieldCompiler(),
                "link_item_filtered" => new SqlFieldCompiler()
            ];
        return self::$compilers;     
    }

    public function compile($name) { return $name; }
}

class SumFieldCompiler extends SqlFieldCompiler
{
    public function compile($name) { return "SUM({$name}::double precision) AS sum_of_{$name}"; }
}

class CountFieldCompiler extends SqlFieldCompiler
{
    public function compile($name) { return "COUNT({$name}) AS count_of_{$name}"; }
}

class AvgFieldCompiler extends SqlFieldCompiler
{
    public function compile($name) { return "AVG({$name}) AS avg_of_{$name}"; }
}

class TextAggFieldCompiler extends SqlFieldCompiler
{
    public function compile($name) { return "array_to_string(array_agg(DISTINCT {$name}),',') AS text_agg_of_{$name}"; }
}

class SqlQueryBuilder
{
}

class ValidPicksSqlQueryBuilder
{
    //public static function deleteBogusPicks(&$facetsConfig)
    public static function compile($query, $facet, $picks)
    {
        $picks_clause = array_join_surround($picks, ",", "('", "'::text)", "");
        $sql = "
            SELECT DISTINCT pick_id, {$facet->name_column} AS name
            FROM {$query->sql_table}
            JOIN (VALUES {$picks_clause}) AS x(pick_id)
              ON x.pick_id = {$facet->id_column}::text
              {$query->sql_joins}
            WHERE 1 = 1
              {$query->sql_where2}
        ";
        return $sql;
    }
}

class RangeCounterSqlQueryBuilder
{
    // RangeFacetCounter::getQuery($facetsConfig, $facetCode, $intervalQuery)
    public static function compile($query, $facet, $intervalQuery, $direct_count_column)
    {
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
}

class DiscreteCounterSqlQueryBuilder
{
    // DiscreteFacetCounter::getQuery($facetsConfig, $countCode, $aggType = "count")
    public static function compile($query, $facet, $countFacet, $aggType)
    {
        $sql = "
            SELECT category, {$aggType}(value) AS direct_count
            FROM (
                SELECT {$facet->id_column} AS category, {$countFacet->id_column} AS value
                FROM {$query->sql_table}
                     {$query->sql_joins}
                WHERE 1 = 1
                    {$query->sql_where2}
                GROUP BY {$facet->id_column}, {$countFacet->id_column}
            ) AS x
            GROUP BY category;
        ";
        return $sql;
    }
}

class RangeCategoryBoundSqlQueryBuilder
{
    // RangeMinMaxFacetCounter::templateSQL($query, $facet, $facetCode): string
    public function compile($query, $facet, $facetCode)
    {
        $where_clause = str_prefix("WHERE ", $facet->query_cond);
        $sql = "
             SELECT '$facetCode' AS facet_code, MAX({$facet->id_column}::real) AS max, MIN({$facet->id_column}::real) AS min
             FROM {$facet->table} 
               {$query->sql_joins} 
             $where_clause";
        return $sql;
    }
}

class FacetContentExtraRowInfoSqlQueryBuilder
{
    // FacetContentLoader::getExtraRowInfo
    public static function compile($query, $facet)
    {
        $sql = "
            SELECT DISTINCT id, name
            FROM (
                SELECT {$facet->id_column} AS id, COALESCE({$facet->name_column},'No value') AS name, {$facet->sort_column} AS sort_column
                FROM {$query->sql_table} 
                     {$query->sql_joins}
                WHERE 1 = 1
                  {$query->sql_where2}
                GROUP BY name, id, sort_column
                ORDER BY {$facet->sort_column}
            ) AS tmp
        ";
        return $sql;
    }
}

class RangeLowerUpperSqlQueryBuilder
{
    // RangeFacetContentLoader::computeRangeLowerUpper($facetCode)
    public static function compile($query, $facet)
    {
        $sql = "
          SELECT MIN({$facet->id_column}) AS lower, MAX({$facet->id_column}) AS upper
          FROM {$facet->table}
        ";
        return $sql;
    }
}

class RangeIntervalSqlQueryBuilder
{
    // RangeFacetContentLoader::getRangeQuery($interval, $min_value, $max_value, $interval_count)
    public static function compile(...$args)
    {
        list($interval, $min, $max, $interval_count) = $args;
        $pieces = [];
        for ($i = 0, $lower = $min; $i <= $interval_count && $lower <= $max; $i++) {
            $upper = $lower + $interval;
            $pieces[] = "($lower, $upper, '$lower => $upper', '')";
            $lower += $interval;
        }
        $values = implode("\n,", $pieces);
        $sql = "
            SELECT lower, upper, id, name 
            FROM (VALUES $values) AS X(lower, upper, id, name)
        ";
        return $sql;
    }
}

class DiscreteContentSqlQueryBuilder
{
    // DiscreteFacetContentLoader::compileSQL($facetsConfig, $facet, $query): string
    public static function compile($query, $facet, $text_filter)
    {
        $text_criteria = empty($text_filter) ? "" : " AND {$facet->name_column} ILIKE '$text_filter' ";
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

}

class ResultSqlQueryBuilder
{
    // ResultSqlQueryCompiler::compile($query, $queryConfig): string
    public static function compile($query, $facet, $queryConfig)
    {
        $data_fields           = implode(", ", $queryConfig['data_fields']);
        $group_by_inner_fields = implode(", ", $queryConfig['group_by_inner_fields']);
        $data_fields_alias     = implode(", ", $queryConfig['data_fields_alias']);
        $group_by_clause       = str_prefix("GROUP BY ", implode(", ", $queryConfig['group_by_fields']));
        $sort_by_clause        = str_prefix("ORDER BY ", implode(", ", $queryConfig['sort_fields']));

        $sql = "
            SELECT $data_fields
            FROM (
                SELECT $data_fields_alias
                FROM {$query->sql_table}
                    {$query->sql_joins}
                WHERE 1 = 1 {$query->sql_where2}
                GROUP BY $group_by_inner_fields
            ) AS X 
            $group_by_clause
            $sort_by_clause
        ";
        return $sql;
    }
}

class MapResultSqlQueryBuilder
{
    // MapResultSqlQueryCompiler::compileSQL($query, $facet): string
    public static function compile($query, $facet)
    {
        $sql = "
            SELECT DISTINCT {$facet->name_column} AS name, latitude_dd, longitude_dd, {$facet->id_column} AS id_column
            FROM {$query->sql_table}
                {$query->sql_joins}
            WHERE 1 = 1 {$query->sql_where2}
        ";
        return $sql;
    }
}
