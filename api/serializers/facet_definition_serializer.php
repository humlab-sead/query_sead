<?php

require_once (__DIR__ . '/../../server/lib/utility.php');

class FacetDefinitionSerializer {

    public static function toJSON($facet_definition, $facet_range)
    {
        $default_slots_num = 0;
        $items = [];
        foreach ($facet_definition as $facet_key => $facet)
        {
            if ($facet["applicable"] != "1")
                continue;

            $counting_title = value_default($facet["counting_title"], "Number of observations");
            $parents = is_array($facet["parents"]) && count($facet["parents"]) > 0 ? "['" . implode("', '", $facet["parents"]) . "']" : "[]";

            $use_text_search = $facet["use_text_search"] ? "1" : "0";

            $items[] =<<<EOS
    {
        "id":               "{$facet_key}",
        "name":             "{$facet['display_title']}",
        "display_title":    "{$facet['display_title']}",
        "facet_type":       "{$facet['facet_type']}",
        "category":         "{$facet['category']}",
        "slot":             "{$facet['slot']}",
        "default":          "{$facet['default']}",
        "counting_title":   "{$counting_title}",
        "use_text_search":  "$use_text_search",
        "max":              "{$facet_range[$facet_key]["max"]}",
        "min":              "{$facet_range[$facet_key]["min"]}",
        "color":            "003399",
        "parents":          $parents
    }
EOS;
            $default_slots_num += $facet["default"] ? 1 : 0;
        }
        $out  = "var facets = [" . implode(", ", $items). "];\n";
        $out .= "var facet_default_slots_num = $default_slots_num;";
        return $out;
    }
}
?>
