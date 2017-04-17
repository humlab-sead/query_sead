<?php
error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

require_once __DIR__ . "/config/environment.php";
require_once __DIR__ . "/config/bootstrap_application.php";
require_once __DIR__ . '/facet_config.php';
require_once __DIR__ . '/facet_content_loader.php';
require_once __DIR__ . '/query_builder.php';
require_once __DIR__ . '/sql_query_builder.php';
require_once __DIR__ . '/lib/utility.php';

class ResultSqlQueryCompiler {

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

                $fieldCompiler = SqlFieldCompiler::getCompiler($result_item_type);

                foreach ($definition_item as $item_type => $item) {

                    $alias_name              = "alias_" . $alias_counter++;
                    $data_fields_alias[]     = "{$item->column} AS {$alias_name}";
                    $data_tables[]           = $item->table;
                    $group_by_inner_fields[] = "{$alias_name}";

                    if (SqlFieldCompiler::isFieldType($result_item_type))
                        $data_fields[] = $fieldCompiler->compile($alias_name);

                    if (SqlFieldCompiler::isGroupByType($result_item_type))
                        $group_by_fields[] = $alias_name;

                    if (SqlFieldCompiler::isSortType($result_item_type))
                        $sort_fields[] = $alias_name;
                }
            }
        }
        $queryConfig = [
            "data_fields"           => $data_fields,
            "group_by_fields"       => $group_by_fields,
            "group_by_inner_fields" => $group_by_inner_fields,
            "data_fields_alias"     => $data_fields_alias,
            "sort_fields"           => $sort_fields,
            "data_tables"           => array_unique($data_tables ?? [])
        ];
        return $queryConfig;
    }

    public static function compile($facetsConfig, $resultConfig)
    {
        $facetCode = "result_facet";
        $queryConfig = self::createResultQueryConfig($resultConfig);

        if (empty($queryConfig) || empty($queryConfig["data_fields"])) {
            return "";
        }
        $query = QuerySetupService::setup2($facetsConfig, $facetCode, $queryConfig["data_tables"]);
        $sql   = ResultSqlQueryBuilder::compile($query, NULL, $queryConfig);
        return $sql;
    }
}

class MapResultSqlQueryCompiler {

    public static function compile($facetsConfig, $facetCode)
    {
        $query = QuerySetupService::setup2($facesConfig, $facetCode);
        $facet = FacetRegistry::getDefinition($facetCode);
        $sql   = MapResultSqlQueryBuilder::compile($query, $facet);
        return $sql;
    }
}

?>