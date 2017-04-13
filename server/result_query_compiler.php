<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);
/*
file: result_query_compiler.php
This file holds for handling queries and returns content for the client.
see also:
parameters functions - <facet_config.php>
*/

require_once __DIR__ . "/config/environment.php";
require_once __DIR__ . "/config/bootstrap_application.php";
require_once __DIR__ . '/facet_config.php';
require_once __DIR__ . '/facet_content_loader.php';
require_once __DIR__ . '/query_builder.php';
require_once __DIR__ . '/lib/utility.php';

class FieldCompiler
{
    protected static $compilers = NULL;

    public static function getCompiler($type)
    {
        return self::isFieldType($type) ? self::getCompilers()[$type] : NULL;
    }

    public static function isFieldType($type)
    {
        return array_key_exists($type, self::getCompilers());
    }

    public static function isSingleItemType($type)
    {
        return self::isFieldType($type) && (get_class(self::getCompiler($type)) == "FieldCompiler");
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
                "single_item" => new FieldCompiler(),
                "link_item" => new FieldCompiler(),
                "link_item_filtered" => new FieldCompiler()
            ];
        return self::$compilers;     
    }

    public function compile($name) { return $name; }
}

class SumFieldCompiler extends FieldCompiler
{
    public function compile($name) { return "sum({$name}::double precision) As sum_of_{$name}"; }
}

class CountFieldCompiler extends FieldCompiler
{
    public function compile($name) { return "count({$name}) As count_of_{$name}"; }
}

class AvgFieldCompiler extends FieldCompiler
{
    public function compile($name) { return "avg({$name}) As avg_of_{$name}"; }
}

class TextAggFieldCompiler extends FieldCompiler
{
    public function compile($name) { return "array_to_string(array_agg(distinct {$name}),',') As text_agg_of_{$name}"; }
}

class ResultQueryCompiler {

    private static function createResultQueryConfig($resultConfig)
    {
        if (empty($resultConfig->items)) {
            return NULL;
        }

        $group_by_fields = $group_by_inner_fields = [];
        $data_tables = $data_fields = [];
        $sort_fields = [];
        $alias_counter = 1;
        $data_fields_alias = [];

        foreach ($resultConfig->items as $aggregate_level) {
            if (empty($aggregate_level)) {
                continue;
            }
            foreach (ResultDefinitionRegistry::getDefinition($aggregate_level)->fields as $result_item_type => $definition_item) {

                $fieldCompiler = FieldCompiler::getCompiler($result_item_type);

                foreach ($definition_item as $item_type => $item) {

                    $alias_name              = "alias_" . $alias_counter++;
                    $data_fields_alias[]     = "{$item->column} AS {$alias_name}";
                    $data_tables[]           = $item->table;
                    $group_by_inner_fields[] = "{$alias_name}";

                    if (FieldCompiler::isFieldType($result_item_type))
                        $data_fields[] = $fieldCompiler->compile($alias_name);

                    if (FieldCompiler::isGroupByType($result_item_type))
                        $group_by_fields[] = $alias_name;

                    if (FieldCompiler::isSortType($result_item_type))
                        $sort_fields[] = $alias_name;
                }
            }
        }
        $queryConfig = [
            "data_fields"        => implode(", ", $data_fields),
            "group_by_str"       => implode(", ", $group_by_fields),
            "group_by_clause"    => str_prefix("GROUP BY ", implode(", ", $group_by_fields)),
            "group_by_str_inner" => implode(", ", $group_by_inner_fields),
            "data_fields_alias"  => implode(", ", $data_fields_alias),
            "sort_fields"        => implode(", ", $sort_fields),
            "sort_by_clause"     => str_prefix("ORDER BY ", implode(", ", $sort_fields)),
            "data_tables"        => array_unique($data_tables ?? [])
        ];
        return $queryConfig;
    }

    public static function compileQuery($facetsConfig, $resultConfig)
    {
        $facetCode = "result_facet";
        $queryConfig = self::createResultQueryConfig($resultConfig);

        if (empty($queryConfig) || empty($queryConfig["data_fields"])) {
            return "";
        }

        $query = QueryBuildService::compileQuery2($facetsConfig, $facetCode, $queryConfig["data_tables"]);

        $sql = self::compileSQL($query, $queryConfig);
        return $sql;
    }

    public static function compileSQL($query, $queryConfig): string
    {
        $sql = <<<EOS
    SELECT {$queryConfig['data_fields']}
    FROM (
        SELECT {$queryConfig['data_fields_alias']}
        FROM {$query->sql_table}
             {$query->sql_joins}
        WHERE 1 = 1 {$query->sql_where2}
        GROUP BY {$queryConfig['group_by_str_inner']}
    ) AS X 
    {$queryConfig['group_by_clause']}
    {$queryConfig['sort_by_clause']}
EOS;
        return $sql;
    }
}

class MapResultQueryCompiler {

    public static function compileQuery($facetsConfig, $facetCode)
    {
        $query = QueryBuildService::compileQuery2($facesConfig, $facetCode);
        $facet = FacetRegistry::getDefinition($facetCode);
        $sql = self::compileSQL($query, $facet);
        return $sql;
    }

    public static function compileSQL($query, $facet): string
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

?>