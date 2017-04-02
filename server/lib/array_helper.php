<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE); // | E_NOTICE);

class ArrayHelper {
    /*
    Function: findIndex
    Parameters:
    * $rows of the particular facet
    * $search_str the text string to position the facet
    Returns: row of facet where the search element is closest to found
    see also
    <findExactIndex>
    <findClosestIndex>
    */

    public static function findIndex($rows, $search_str, $key = "name")
    {
        $pos = ArrayHelper::findExactIndex($rows, $search_str, $key);
        if ($pos == -1) {
            $pos = ArrayHelper::findClosestIndex($rows, $search_str, $key);
        }
        return $pos;
    }

    /*
    Function: finfindExactIndexdIndex
    Parameters:
    * $rows of the particular facet
    * $search_str the text string to position the facet
    Returns:
    row of facet where the search element is closest to found
    */

    private static function findExactIndex($rows, $search_str, $key)
    {
        $search_str = mb_strtoupper($search_str, "utf-8");
        if (isset($rows)) {
            $position = 0; 
            foreach ($rows as $row) {
                $compare_to_str = substr($row[$key], 0, strlen($search_str));
                if (strcasecmp($search_str, $compare_to_str) == 0) {
                    return $position;
                }
                $position++;
            }
        }
        return -1;
    }

    /*
    Function:  findClosestIndex
    Parameters:
    * $rows of the particular facet
    * $search_str the text string to position the facet
    */

    private static function findClosestIndex($rows, $search_str, $key)
    {
        $start_position = 0;
        $search_str = mb_strtoupper($search_str, "utf-8");
        $end_position = count($rows) - 1;
        $position = floor(($end_position - $start_position) / 2);
        if (!empty($rows)) {
            $found = false;
            while (($end_position - $start_position) > 1 && $found == false) {
                $row = $rows[$position];
                $compare_to_str = substr($row[$key], 0, strlen($search_str));
                if (strcasecmp($search_str, $compare_to_str) == 0) {
                    $found = true;
                } elseif (strcasecmp($search_str, $row[$key]) < 0) {
                    $start_position = $start_position;
                    $end_position = $position;
                    $position = $start_position + floor(($end_position - $start_position) / 2);
                } elseif (strcasecmp($search_str, $row[$key]) > 0) {
                    $start_position = $position;
                    $end_position = $end_position;
                    $position = $start_position + ceil(($end_position - $start_position) / 2);
                }
            }
        }
        return $position;
    }


}

function array_add_unique(&$array, $mixed)
{
    $values = (empty($mixed) ? [] : (is_array($mixed) ? $mixed : [ $mixed ]));
    foreach ($values as $value) {
        $array[$value] = $value;
    }
}
?>
