<?php
/*

Definition how to join tables ($join_columns):
This is essentially a graph where the nodes are the tables and the edges are the join-condition.

It is stored as an associtative matrix (a edge-list) called join_columns where the keys are the original table name (from_table) and the target table name (to_table).
The keys can be seen as the nodes in the graph and edges are holding the join-condition as the following

key home_column = the column name in the source table identified as the  left hand side in the sql join
key remote_column = the column name in the target table  identified as the right hand side in the sql join.

For example, table a has a relationship
with table b via a column called b_id. This column points via a foreign key to the identifier id  of table b. Thus the array saved at edge ['a']['b'] in join_columns will look like the following
array(home_column = 'b_id',remote_column = 'a.id')

This will later on be concatenated into the join clause (usually theta joins)
a.b_id = b.id

*/

require_once __DIR__ . "/../connection_helper.php";

class WeightedGraph {

    // Defines the relations between table columns i.e. the DB model

    public $joinColumns;
    public $tableIds;
    public $edges;
    public $aliasTables;

    private function createJoinElement($sourceTable, $sourceColumn, $targetTable, $targetColumn, $weight)
    {
        return [
            "home_columns"      => [ "$sourceTable.\"$sourceColumn\"" ],
            "remote_columns"    => [ "$targetTable.\"$targetColumn\"" ],
            "join_condition"    => " inner join $targetTable on ($sourceTable.\"$sourceColumn\"=$targetTable.\"$targetColumn\")\n",
            "weight"            => $weight
        ];
    }

    private function fetchJoinRelations()
    {
        $relations = [];
        $rs = ConnectionHelper::query("SELECT * FROM metainformation.tbl_foreign_relations");
        while ($row = pg_fetch_assoc($rs))
        {
            $sourceTable = $row["source_table"];
            $targetTable = $row["target_table"];
            $sourceColumn = $row["source_column"];
            $targetColumn = $row["target_column"];
            $weight = $row["weight"];
            $relations[$sourceTable][$targetTable] = $this->createJoinElement($sourceTable, $sourceColumn, $targetTable, $targetColumn, $weight);
            $relations[$targetTable][$sourceTable] = $this->createJoinElement($targetTable, $targetColumn, $sourceTable, $sourceColumn, $weight);
        }
        return $relations;
    }

    public function getJoinColumns()
    {
        global $facet_definition;
        // Define the table graph

        // Make all joins unidirectional i.e. same join condition no matter direction
        // Add the keys unique array of tables, where the keys are the table names
        // Representing the graph as a matrix
        // also creation of help variables for looking up tables names against number and vice versa

        /*
        * This will construct an associtative matrix called join_columns.
        * where the keys are the original table name (from_table) and the target table name
        * (to_table). The value stored in each position is an array describing the relation to travers these
        * two tables: key home_column = the column name in the source table identified as the
        * left hand side in the sql join; key remote_column = the column name in the target table
        * identified as the right hand side in the sql join. For example, table a has a relationship
        * with table b via a column called b_id. This column points via a foreign key to the identifier id
        * of table b. Thus the array saved at position ['a']['b'] in join_columns will look like the following
        * array(
        *      home_column = 'b_id',
        *      remote_column = 'id')
        *
        * This will later on be concatenated into the join clause (usually theta joins):
        *      a.b_id = b.id
        */
        $join_columns = $this->fetchJoinRelations();

        foreach ($facet_definition as $facet_key => $facet_definition_temp)
        {
            if (!array_key_exists('alias_table', $facet_definition_temp))
                continue;
            $alias_table = $facet_definition_temp['alias_table'];
            if (empty($facet_definition_temp['alias_table']))
                continue;
            $source_table = $facet_definition_temp["table"];
            $temp_joins_conditions = $join_columns[$source_table];
            foreach ($temp_joins_conditions as $remote_table => $join_info_array)
            {
                $join_info_array["home_columns"]   = str_replace($source_table, $alias_table, $join_info_array["home_columns"]);
                $join_info_array["remote_columns"] = str_replace($source_table, $alias_table, $join_info_array["remote_columns"]);
                $join_info_array["join_condition"] = str_replace($source_table, $alias_table, $join_info_array["join_condition"]);

                $join_columns[$alias_table][$remote_table] = $join_info_array;
                $join_columns[$remote_table][$alias_table] = $join_info_array;
            }
        }
        return $join_columns;
    }

    public function getAliasTables() {
        global $facet_definition;
        $tables = [];
        foreach ($facet_definition as $facet_key => $item)
        {
            if (isset($item["alias_table"]))
                $tables[$item["alias_table"]] = $item["table"];
        }
        return $tables;
    }

    public function generateTableIds($join_columns) {
        return array_flip(array_unique(array_keys($join_columns)));
    }

    public function createGraph($join_columns, $tableIds)
    {
        // Create the graph ie the edges between the nodes into the matrix
        $edges = [];
        foreach ($join_columns as $key => $pair)
        {
            foreach ($pair as $key2 => $element)
            {
                $weight = $join_columns[$key][$key2]["weight"];
                $edges[$tableIds[$key]][$tableIds[$key2]] = intval($weight);
            }
        }
        return $edges;
    }

    public function setup()
    {
        $this->joinColumns = $this->getJoinColumns();
        $this->tableIds = $this->generateTableIds($this->joinColumns);
        $this->edges = $this->createGraph($this->joinColumns, $this->tableIds);
        $this->aliasTables = $this->getAliasTables();
    }

    public static $weightedGraph = NULL;
    public static function create()
    {
        if (self::$weightedGraph == NULL) {
            self::$weightedGraph = new WeightedGraph();
            self::$weightedGraph->setup();
        }
        return self::$weightedGraph;
    }
}

?>