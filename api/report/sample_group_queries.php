<?php

require_once(__DIR__ . '/../../server/connection_helper.php');

/*
* class: sample_group_query
* function to render query on sample_group level
*/

class sample_group_query
{

    /*
    * function: run_query
    * if fail, outputs query and exit.
    *
    * returns:
    * resultset of query
    */
    private function run_query($q)
    {
        $rs = ConnectionHelper::query($q);
        return $rs;
    }

    private function get_facet_filter($f_code, $cache_id)
    {
        global $facet_definition;
        if (empty($cache_id))
            return "";
        return " and  " . $facet_definition[$f_code]["id_column"] . "  in (" . get_f_code_filter_query($cache_id, $f_code) . ") ";
    }

    /**
     * Function arrange_species_data
     * Arrange abundance report on sample group level
     * First get all sample id and store those in a list.
     * Then get all abundances arranged by sample_id, using array_agg function in postgres
     * Then use those two list to make a transposed matrix
     * params:
     *  $sample_group_id  -
     *  $cache_id - reference to filters set by users stored as xml
     *
     * returns:
     *  matrix of data as array
     *
     * see also:
     * <get_f_code_filter_query>
     */
    function arrange_species_data($sample_group_id, $method_id, $cache_id)
    {

        $q = $this->get_physical_sample_id_query($sample_group_id, $method_id, $cache_id); // echo SqlFormatter::format($q,true);
        $rs = $this->run_query($q);
        $physical_samples = [];
        while ($row = pg_fetch_assoc($rs)) {
            $physical_samples[$row["physical_sample_id"]]["sample_name"] = $row["sample_name"] . " "; //<BR>(".$row["physical_sample_id"].")";
            $physical_samples[$row["physical_sample_id"]]["site_name"] = $row["site_name"] . " "; //<BR>(".$row["physical_sample_id"].")";
            $physical_samples[$row["physical_sample_id"]]["sample_group_name"] = $row["sample_group_name"] . " "; //<BR>(".$row["physical_sample_id"].")";
        }

        $q = $this->render_species_count_query($sample_group_id, $method_id, $cache_id); // echo SqlFormatter::format($q,true);
        $species_list = $this->get_species_list($q);

        if (!empty($physical_samples)) {
            $data_array[0][0] = "Taxon || Physical sample";
            foreach ($physical_samples as $sample_id => $sample_obj) {
                // header
                if (empty($sample_group_id)) {
                    $data_array[0][$sample_id] = $sample_obj["sample_name"] . "@" . $sample_obj["sample_group_name"] . "@" . $sample_obj["site_name"];
                } else {
                    $data_array[0][$sample_id] = $sample_obj["sample_name"];
                }
            }
        }

        $row_count = 1;
        if (isset($species_list)) {
            foreach ($species_list as $specie => $physical_sample_abundance) {
                $data_array[$row_count][0] = $specie;
                foreach ($physical_samples as $physical_sample_id => $physical_sample_name) {
                    // lookup if exist and store empty or a value
                    $data_array[$row_count][$physical_sample_id] = $physical_sample_abundance[$physical_sample_id];
                }
                $row_count++;
            }
        }
        return $data_array;
    }

    /*
    * function: get_site_query
    * Report with basic site info for the sample group(s)
    * Used filter if set or uses sample_group id if set.
    * params:
    * $sample_group_id - id
    * $cache_id - filter reference
    * returns: query
    *
    *
    * see also:
    * <get_f_code_filter_query>
    */

    function get_site_query($sample_group_id, $cache_id)
    {
        $f_code = "sites_helper";
        $facet_filtered = $this->get_facet_filter($f_code, $cache_id);
        $filter = !empty($sample_group_id) ? " tbl_sample_groups.sample_group_id = $sample_group_id" : "1=1 ";
        $q = "
        select site_name
        from tbl_sample_groups
        join tbl_sites
          on tbl_sample_groups.site_id=tbl_sites.site_id
        where
         $filter
         $facet_filtered
        group by site_name
        order by site_name";

        return $q;
    }

    /*
    * function get_sample_group_query
    * Get query for basic sample group(s) info
    *
    * params:
    * $sample_group_id - id
    * $cache_id - filter reference
    *
    * returns:
    *  query
    *
    * see also:
    * <get_f_code_filter_query>
    */

    function get_sample_group_query($sample_group_id, $cache_id)
    {
        $f_code = "sample_groups_helper";
        $facet_filtered = $this->get_facet_filter($f_code, $cache_id);
        $filter = !empty($sample_group_id) ? " tbl_sample_groups.sample_group_id = $sample_group_id" : "1=1 ";
        $q = "
        select tbl_sample_groups.site_id as site_id,
            sample_group_name as \"Sample group name\",
            sample_group_description,
            method_name
        from tbl_sample_groups
        left join tbl_methods
          on tbl_sample_groups.method_id=tbl_methods.method_id
        where
            $filter
            $facet_filtered
        group by
            tbl_sample_groups.site_id,
            sample_group_name,
            sample_group_description,
            method_name
        order by
            tbl_sample_groups.site_id,
            sample_group_name  ,
            sample_group_description ,
            method_name               ";

        return $q;
    }

    /**
     * function get_physical_sample_id_query
     * Get the id of the physical sample for a sample group or a set of sample groups
     * params:
     * $sample_group_id - id
     * $cache_id - filter reference
     * returns:
     *  id of samples
     *
     * see also:
     * <get_f_code_filter_query>
     */
    public function get_physical_sample_id_query($sample_group_id, $method_id, $cache_id)
    {
        $f_code = "physical_samples";
        $facet_filtered = $this->get_facet_filter($f_code, $cache_id);
        $filter = !empty($sample_group_id) ? " tbl_physical_samples.sample_group_id = $sample_group_id" : "1=1 ";
        $q = "
        select tbl_sites.site_name as site_name,
            sample_group_name,
            sample_name,
            tbl_physical_samples.physical_sample_id
        from tbl_physical_samples
        left join tbl_sample_groups
          on tbl_sample_groups.sample_group_id=tbl_physical_samples.sample_group_id
        join tbl_analysis_entities
          on tbl_analysis_entities.physical_sample_id=tbl_physical_samples.physical_sample_id
        join tbl_datasets
          on tbl_analysis_entities.dataset_id=tbl_datasets.dataset_id
        join tbl_methods
          on tbl_datasets.method_id=tbl_methods.method_id
        left join tbl_sites
          on tbl_sample_groups.site_id= tbl_sites.site_id
        where
            tbl_methods.method_id=$method_id  and
            $filter
            $facet_filtered
        group by
            sample_name,
            sample_group_name,
            tbl_sites.site_name,
            tbl_physical_samples.physical_sample_id
        order by sample_name; ";

        return $q;
    }

    /**
     * function: render_species_count_query
     * Query of counting species in each sample for sample gruop of set of sample_group
     * $facet_definition- global
     * $sample_group_id - id
     * $cache_id - filter refer
     * returns query
     *
     * see also:
     * <get_f_code_filter_query>
     */
    public function render_species_count_query($sample_group_id, $method_id, $cache_id)
    {
        $f_code = "species_helper";
        $facet_filtered = $this->get_facet_filter($f_code, $cache_id);
        $filter = !empty($sample_group_id) ? " tbl_physical_samples.sample_group_id = $sample_group_id" : "1=1 ";
        $q = " 
        select species,
               array_to_string(array_agg(physical_sample_id::text||'|'||abundance),';') as species_list_by_physical_sample_id
        from  (
            select
                COALESCE(tbl_taxa_tree_genera.genus_name,'')||' '||COALESCE(tbl_taxa_tree_master.species,'')||' '||COALESCE('('||tbl_abundance_elements.element_name||')','')::text||' '||COALESCE(tbl_modification_types.modification_type_name::text,'')    as  species
                --     tbl_taxa_tree_genera.genus_name||' '||tbl_taxa_tree_master.species   as  species    ,
                ,tbl_physical_samples.physical_sample_id,
                tbl_abundances.abundance  as abundance
            from tbl_datasets
            left join tbl_analysis_entities
              on tbl_datasets.dataset_id=tbl_analysis_entities.dataset_id
            left join  tbl_physical_samples
              on tbl_analysis_entities.physical_sample_id=tbl_physical_samples.physical_sample_id
            left join tbl_analysis_entity_prep_methods
              on tbl_analysis_entity_prep_methods.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
            left  join tbl_methods
              on tbl_analysis_entity_prep_methods.method_id=tbl_methods.method_id
            left join tbl_abundances
              on tbl_abundances.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
            left join tbl_abundance_elements
              on tbl_abundances.abundance_element_id=tbl_abundance_elements.abundance_element_id
            left join tbl_abundance_modifications
              on tbl_abundances.abundance_id=tbl_abundance_modifications.abundance_id
            left join tbl_modification_types
              on tbl_modification_types.modification_type_id=tbl_abundance_modifications.modification_type_id
            left  join tbl_taxa_tree_master
              on tbl_abundances.taxon_id = tbl_taxa_tree_master.taxon_id
            left  join tbl_taxa_tree_genera
              on tbl_taxa_tree_master.genus_id = tbl_taxa_tree_genera.genus_id
            where
                trim(species)<>'' and
                tbl_datasets.method_id=$method_id  and
                $filter
                $facet_filtered
        ) as t
        group by species  ";
        return $q;
    }

    /**
     * function: render_dataset_query
     * get the info for the datasets in sample group(s)
     * see also:
     * <get_f_code_filter_query>
     */
    public function render_dataset_query($sample_group_id, $cache_id = null)
    {
        $f_code = "dataset_helper";
        $facet_filtered = $this->get_facet_filter($f_code, $cache_id);
        $q = "select
            tbl_datasets.dataset_id,
            tbl_datasets.dataset_name,
            tbl_methods.method_name,
            aepm.method_name as prep_method_name,
            tbl_methods.method_abbrev_or_alt_name,
            tbl_methods.description,
            tbl_record_types.record_type_name
            from tbl_datasets
            left  join tbl_analysis_entities
            on tbl_analysis_entities.dataset_id = tbl_datasets.dataset_id
            join tbl_physical_samples
            on tbl_analysis_entities.physical_sample_id = tbl_physical_samples.physical_sample_id
            left join tbl_analysis_entity_prep_methods
            on tbl_analysis_entity_prep_methods.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
            left  join tbl_sample_groups
            on tbl_physical_samples.sample_group_id = tbl_sample_groups.sample_group_id
            left join tbl_methods
            on tbl_datasets.method_id=tbl_methods.method_id
            left  join tbl_methods aepm
            on tbl_analysis_entity_prep_methods.method_id=aepm.method_id
            left  join tbl_record_types
            on tbl_methods.record_type_id = tbl_record_types.record_type_id
            where
            tbl_physical_samples.sample_group_id=$sample_group_id
            $facet_filtered
            group by
            tbl_datasets.dataset_id,
            tbl_datasets.dataset_name,
            tbl_methods.method_name,
            tbl_methods.method_abbrev_or_alt_name,
            tbl_methods.description,
            prep_method_name,
            tbl_record_types.record_type_name";
        return $q;
    }

    /**
     * function: render_dataset_methods
     * get the methods of datasets in sample group(s)
     *
     * see also:
     * <get_f_code_filter_query>
     */
    public function render_sample_group_methods_query($sample_group_id, $cache_id = null)
    {
        $f_code = "dataset_helper";
        $facet_filtered = $this->get_facet_filter($f_code, $cache_id);
        $filter = !empty($sample_group_id) ? "  tbl_physical_samples.sample_group_id = $sample_group_id" : "1=1 ";
        $q = "select
                tbl_methods.method_name,
                tbl_methods.method_id
                
                from tbl_datasets
                left  join tbl_analysis_entities
                on tbl_analysis_entities.dataset_id = tbl_datasets.dataset_id
                join tbl_abundances
                on tbl_abundances.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
                join tbl_physical_samples
                on tbl_analysis_entities.physical_sample_id = tbl_physical_samples.physical_sample_id
                left  join tbl_sample_groups
                on tbl_physical_samples.sample_group_id = tbl_sample_groups.sample_group_id
                left join tbl_methods
                on tbl_datasets.method_id=tbl_methods.method_id
                where
                $filter
                $facet_filtered
                group by
                tbl_methods.method_name,
                tbl_methods.method_id ";

        return $q;
    }

    /**
     * Function render_header_measured_values_query
     * Query to get measured values f
     */
    public function render_header_measured_values_query($sample_group_id, $cache_id = null)
    {

        $filter = !empty($sample_group_id) ? "  tbl_physical_samples.sample_group_id = $sample_group_id" : "1=1 ";
        $f_code = "sample_groups";
        $facet_filtered = $this->get_facet_filter($f_code, $cache_id);
        $q = "select
            tbl_datasets.dataset_id,
            dm.method_id::text||'_'||COALESCE(aepm.method_id::text,'NULL')::text as column_id,
            tbl_datasets.dataset_name,
            dm.method_name,
            dm.method_abbrev_or_alt_name,
            dm.description,
            tbl_record_types.record_type_name,
            aepm.method_name as prep_method_name
        FROM tbl_datasets
        left  join tbl_analysis_entities
          on tbl_analysis_entities.dataset_id = tbl_datasets.dataset_id
        left join tbl_analysis_entity_prep_methods
          on tbl_analysis_entity_prep_methods.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
        join tbl_physical_samples
          on tbl_analysis_entities.physical_sample_id = tbl_physical_samples.physical_sample_id
        left join tbl_sample_groups
          on tbl_physical_samples.sample_group_id = tbl_sample_groups.sample_group_id
        left join tbl_methods dm
          on tbl_datasets.method_id=dm.method_id
        left join tbl_methods aepm
          on tbl_analysis_entity_prep_methods.method_id=aepm.method_id
        left join tbl_record_types
          on dm.record_type_id = tbl_record_types.record_type_id
        WHERE
            $filter
            $facet_filtered
        GROUP BY
            prep_method_name,
            column_id,
            tbl_datasets.dataset_id,
            tbl_datasets.dataset_name,
            dm.method_name,
            dm.method_abbrev_or_alt_name,
            dm.description,
            tbl_record_types.record_type_name";
        return $q;
    }

    /**
     * function: render_site_query
     * get basic info about sites for a sample_group
     */
    public function render_site_query($sample_group_id)
    {
        $q = "select distinct tbl_sites.site_name, tbl_sites.site_id
        from tbl_datasets
        left  join tbl_analysis_entities
          on tbl_analysis_entities.dataset_id = tbl_datasets.dataset_id
        join tbl_physical_samples
          on tbl_analysis_entities.physical_sample_id = tbl_physical_samples.physical_sample_id
        left  join tbl_sample_groups
          on tbl_physical_samples.sample_group_id = tbl_sample_groups.sample_group_id
        inner  join tbl_sites
          on tbl_sample_groups.site_id = tbl_sites.site_id
        where tbl_physical_samples.sample_group_id=$sample_group_id;";
        return $q;
    }

    /*
    * function: render_query_measured_values_by_sample_id
    * Get the query for a composite string object  with sample id and values of for different dataset/methods
    */

    function render_query_measured_values_by_sample_id($sample_group_id, $cache_id = null)
    {
        $f_code = "result_facet";
        $facet_filtered = $this->get_facet_filter($f_code, $cache_id);
        $filter = !empty($sample_group_id) ? "  tbl_physical_samples.sample_group_id = $sample_group_id" : "1=1 ";
        $q = "  select
            tbl_physical_samples.physical_sample_id ,
            tbl_physical_samples.sample_name,
            tbl_sites.site_name,
            tbl_sample_groups.sample_group_name,
            array_to_string(array_agg(dm.method_id::text||'_'||COALESCE(aepm.method_id::text,'NULL')::text||'|'||to_char(tbl_measured_values.measured_value, '99999999.999')),';') as dataset_value_composites
        from tbl_datasets
        left join tbl_analysis_entities
          on tbl_datasets.dataset_id=tbl_analysis_entities.dataset_id
        left join tbl_analysis_entity_prep_methods
          on tbl_analysis_entity_prep_methods.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
        left join  tbl_physical_samples
          on tbl_analysis_entities.physical_sample_id=tbl_physical_samples.physical_sample_id
        left    join tbl_methods dm
          on tbl_datasets.method_id=dm.method_id
        left join tbl_methods aepm
          on tbl_analysis_entity_prep_methods.method_id=aepm.method_id
        left join tbl_sample_groups
          on  tbl_physical_samples.sample_group_id=tbl_sample_groups.sample_group_id
        left join tbl_sites
          on tbl_sites.site_id=tbl_sample_groups.site_id
        inner join tbl_measured_values
          on tbl_measured_values.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
        where
          $filter
          $facet_filtered
        group by
          tbl_physical_samples.physical_sample_id,
          tbl_physical_samples.sample_name,
          tbl_sites.site_name,
          tbl_sample_groups.sample_group_name";

        return $q;
    }

    /*
    * Function: arrange_measured_values
    * Create a matrix with sample id and method and their corresponding measured value.
    * Sample ids are rows and method/datasets are the columns.
    */

    function arrange_measured_values($sample_group_id, $cache_id)
    {
        // get all dataset names as headings and dataset_id as key in array
        $q = $this->render_header_measured_values_query($sample_group_id, $cache_id);
        $rs = $this->run_query($q);

        while ($row = pg_fetch_assoc($rs)) {
            $data_set_list[$row["column_id"]] = "" . $row["method_name"] . "  " . $row["prep_method_name"]; //. " [".$row["column_id"]."]"; // add method in heading... prep_method_name
        }

        if (isset($data_set_list)) {
            $data_array[0][0] = "Sample name /|/ dataset ";
            foreach ($data_set_list as $dataset_id => $dataset_description) {
                // header
                $data_array[0][$dataset_id] = $dataset_description;
            }
        }

        $q = $this->render_query_measured_values_by_sample_id($sample_group_id, $cache_id);
        $rs = $this->run_query($q);
        $sample_names = $data_set_list = $data_array = [];
        while ($row = pg_fetch_assoc($rs)) {
            $sample_items = explode(";", $row["dataset_value_composites"]);
            foreach ($sample_items as $sample_key => $element2) {
                // SAMPLE GROUP|ABUNDANCE
                //      // 1740|5.4000000000;396|52.0000000000;1263|18.0000000000;1661|18.0000000000;1263|216.0000000000
                $parts = explode("|", $element2);
                // echo $parts[0]; //part[0]= dataset_id
                $values_list_by_sample_and_dataset[$row["physical_sample_id"]][$parts[0]] = $parts[1];
                if (!empty($sample_group_id)) {
                    $sample_names[$row["physical_sample_id"]] = $row["sample_name"]; //." | ".$row["physical_sample_id"];
                } else {
                    $sample_names[$row["physical_sample_id"]] = $row["sample_name"] . "@" . $row["sample_group_name"] . "@" . $row["site_name"]; //." | ".$row["physical_sample_id"];
                }
            }
        }
        $row_count = 1;
        if (isset($values_list_by_sample_and_dataset)) {
            foreach ($values_list_by_sample_and_dataset as $sample_key => $measured_values) {
                $data_array[$row_count][$sample_key] = $sample_names[$sample_key]; // $sample_key;// sample_name
                foreach ($data_set_list as $dataset_id => $dataset_name) {
                    $data_array[$row_count][$dataset_id] = $measured_values[$dataset_id];
                }
                $row_count++;
            }
        }
        return $data_array;
    }
}

?>