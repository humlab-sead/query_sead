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

    private static function getResultQueryConfig($result_definition, $facetConfig, $resultConfig)
    {

        if (empty($resultConfig["items"])) {
            return NULL;
        }

        $group_by_fields = $group_by_inner_fields = [];
        $data_tables = $data_fields = [];
        $sort_fields = [];
        $alias_counter = 1;
        $data_fields_alias = [];

        foreach ($resultConfig["items"] as $aggregate_level) {
            if (empty($aggregate_level)) {
                continue;
            }
            foreach ($result_definition[$aggregate_level]["result_item"] as $result_item_type => $definition_item) {

                $fieldCompiler = FieldCompiler::getCompiler($result_item_type);

                foreach ($definition_item as $item_type => $item) {

                    $alias_name = "alias_" . $alias_counter++;
                    $data_fields_alias[] = "{$item['column']} AS {$alias_name}";
                    $data_tables[] = $item["table"];
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
        if (!empty($data_tables)) {
            $data_tables = array_unique($data_tables); // Removes multiple instances of same table.
        }
        $queryConfig = [
            "data_fields"        => implode(", ", $data_fields),
            "group_by_str"       => implode(", ", $group_by_fields),
            "group_by_str_inner" => implode(", ", $group_by_inner_fields),
            "data_fields_alias"  => implode(", ", $data_fields_alias),
            "sort_fields"        => implode(", ", $sort_fields),
            "data_tables"        => $data_tables
        ];
        return $queryConfig;
    }

    //***************************************************************************************************************************************************
    /*
    function: compileQuery
    Function the generates the sql-query of html-output and data to download
    there are different type of variables which affects the aggregation functinoality in the query.
    It uses the "result_facet as a starting point and adds all the selected variables to be included in the output.
    For aggregated values there is countíng column being defined for each result variable
    see also:
    <get_facet_content>
    <get_joins>
    */
    public static function compileQuery($facetConfig, $resultConfig)
    {
        global $result_definition;

        $facetCode = "result_facet";

        $queryConfig = self::getResultQueryConfig($result_definition, $facetConfig, $resultConfig);

        if (empty($queryConfig) || empty($queryConfig["data_fields"])) {
            return "";
        }

        $facetCodes = FacetConfig::getCodesOfActiveFacets($facetConfig);
        $facetCodes[] = $facetCode;
        
        $query = QueryBuildService::compileQuery($facetConfig, $facetCode, $queryConfig["data_tables"], $facetCodes);

        $where_clause    = str_prefix("AND ", $query['where']);
        $group_by_clause = str_prefix("GROUP BY ", $queryConfig["group_by_str"]);
        $sort_by_clause  = str_prefix("ORDER BY ", $queryConfig["sort_fields"]);

        $sql =<<<EOS
    SELECT {$queryConfig["data_fields"]}
    FROM (
        SELECT {$queryConfig["data_fields_alias"]}
        FROM {$query['tables']}
             {$query['joins']}
        WHERE 1 = 1  
         $where_clause
        GROUP BY {$queryConfig["group_by_str_inner"]}
    ) AS tmp 
    $group_by_clause
    $sort_by_clause
EOS;
        return $sql;
    }
}

class MapResultQueryCompiler {

    public static function compileQuery($facetConfig, $facetCode)
    {
        $facetCode = "map_result"; // override argument
        $facetCodes = FacetConfig::getCodesOfActiveFacets($facetConfig);
        $facetCodes[] = $facetCode; 
        $facet = FacetRegistry::getDefinition($facetCode);

        $query = QueryBuildService::compileQuery($facetConfig, $facetCode, [], $facetCodes);

        $filter_clause = str_prefix("AND ", $query["where"]);	
        $sql = <<<EOX
    SELECT DISTINCT {$facet['name_column']} AS name, latitude_dd, longitude_dd, {$facet['id_column']} AS id_column
    FROM {$query['tables']}
         {$query['joins']}
    WHERE 1 = 1
     $filter_clause
EOX;
        return $sql;
    }
}

?>