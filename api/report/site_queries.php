<?php

/*
  Class :site_query
 * Function to make queries and arrange data on site(s) level
 */

class site_query {
    
      private function run_query($conn, $q) {
        if (($rs = pg_query($conn, $q)) <= 0) {
            echo "Error: cannot execute site query. " . SqlFormatter::format($q,false) . " \n";
            exit;
        }
        //   file_put_contents("sql_log.txt",  SqlFormatter::format($q, false).";\n", FILE_APPEND);
        return $rs;
    }
    
    /*
     * Function: get_site_query
     * get basic info on site level
     * 
     * see also:
     * <get_f_code_filter_query>
     */

    public function get_site_query($site_id, $cache_id = null) {
        global $facet_definition;
        $f_code = "sites_helper";
        if (!empty($cache_id)) {
            $facet_filtered = " and  " . $facet_definition[$f_code]["id_column"] . "  in (" . get_f_code_filter_query($cache_id, $f_code) . ") ";
        }
        $filter = !empty($site_id) ? " tbl_sites.site_id = $site_id" : "1=1 ";
        $q = "select 
                tbl_sites.site_id as \"site_id\", 
                site_name as \"Site name\", 
                site_description as \"Site description\", 
                natgridref as \"National grid ref\", 
                array_to_string(
                  array_agg(
                    location_name 
                    order by 
                      location_type_id desc
                  ), 
                  ','
                ) as Places, 
                preservation_status_or_threat as \"Preservation status or threat\",
                latitude_dd as site_lat, 
                longitude_dd as site_lng 
              From 
                tbl_sites 
                left join tbl_site_locations on tbl_site_locations.site_id = tbl_sites.site_id 
                left join tbl_site_natgridrefs on tbl_site_natgridrefs.site_id = tbl_sites.site_id 
                left join tbl_site_preservation_status on tbl_site_preservation_status.site_preservation_status_id = tbl_sites.site_preservation_status_id 
                left join tbl_locations on tbl_locations.location_id = tbl_site_locations.location_id 
              where 
               $filter
                 $facet_filtered
              group by 
                tbl_sites.site_id, 
                site_name, 
                site_description, 
                natgridref, 
                site_lat, 
                site_lng, 
                preservation_status_or_threat
                 ";

        return $q;
    }

    /*
     * function: get_reference_query
     * Get the basic info for the references of  site(s)
     * params: 
     *  $site_id  - id 
     *  $cache_id - filter  ref by user 
     * see also:
     * <get_f_code_filter_query>
     */

    public function get_reference_query($site_id, $cache_id = null) {

        global $facet_definition;
        $f_code = "tbl_biblio_sites";
        if (!empty($cache_id)) {
            $facet_filtered = " and  " . $facet_definition[$f_code]["id_column"] . "  in (" . get_f_code_filter_query($cache_id, $f_code) . ") ";
        }

        $filter = !empty($site_id) ? " site_id = $site_id" : "1=1 ";

        $q = "  select distinct
                  author || '  ('||year||')'  as Reference, 
                  Title
            from tbl_site_references
                left join
                tbl_biblio
                on tbl_site_references.biblio_id=tbl_biblio.biblio_id
                where 
                $filter 
                $facet_filtered ";
        return $q;
    }

    /*
     * function get get_relative_ages_query
     * Get query for dating information about site(s)
     * params:
     * $site_id - id 
     * $cache_id - reference to filter by user
     * see also:
     * <get_f_code_filter_query>
     */

    function get_relative_ages_query($site_id, $cache_id) {
        global $facet_definition;
        $f_code = "tbl_relative_dates_helper";
        if (!empty($cache_id)) {
            $facet_filtered = " and  " . $facet_definition[$f_code]["id_column"] . "  in (" . get_f_code_filter_query($cache_id, $f_code) . ") ";
        }

        $filter = !empty($site_id) ? "s.site_id = $site_id" : "1=1 ";

        $q = "Select  ps.sample_name,
            ra.\"Abbreviation\",
            l.location_name,
            du.uncertainty,
            m.method_name,
            ra.C14_age_older,
            ra.C14_age_younger,
            ra.CAL_age_older,
            ra.CAL_age_younger,
            ra.relative_age_name,
            ra.notes,
            b.author || '(' || b.year || ')'
          From tbl_relative_dates 
          Join tbl_physical_samples ps
            On ps.physical_sample_id  = tbl_relative_dates.physical_sample_id
          join tbl_sample_groups sg
          on sg.sample_group_id=ps.sample_group_id
          join tbl_sites s
          on s.site_id=sg.site_id
          Join tbl_relative_ages ra
            On ra.relative_age_id = tbl_relative_dates.relative_age_id
          Join tbl_methods m
            On m.method_id = tbl_relative_dates.method_id
          Join tbl_dating_uncertainty du
            On du.dating_uncertainty_id = tbl_relative_dates.dating_uncertainty_id
          Join tbl_relative_age_types rat
            On rat.relative_age_type_id = ra.relative_age_type_id
          Join tbl_locations l
            On l.location_id = ra.location_id
          Join tbl_relative_age_refs raf
            On raf.relative_age_id = ra.relative_age_id
          Join tbl_biblio b
            On b.biblio_id = raf.biblio_id
            where
              $filter
              $facet_filtered      ";

        return $q;
    }

     public function render_species_count_query($site_id, $method_id, $cache_id) {

        global $facet_definition;
        $f_code = "species_helper";

        if (!empty($cache_id)) {
            $facet_filtered = " and " . $facet_definition[$f_code]["id_column"] . " in (" . get_f_code_filter_query($cache_id, $f_code) . ") ";
        }

        $filter = !empty($site_id) ? " tbl_sample_groups.site_id = $site_id" : "1=1 ";

        $q = "     select species,
                     array_to_string(array_agg(physical_sample_id::text||'|'||abundance),';') as species_list_by_physical_sample_id
                   from
                     (
                       select  
                           -- tbl_taxa_tree_genera.genus_name||' '||tbl_taxa_tree_master.species   as  species    ,
                           COALESCE(tbl_taxa_tree_genera.genus_name,'')||' '||COALESCE(tbl_taxa_tree_master.species,'')||' '||COALESCE('('||tbl_abundance_elements.element_name||')','')::text||' '||COALESCE(tbl_modification_types.modification_type_name::text,'')    as  species ,
                            tbl_physical_samples.physical_sample_id,
                            tbl_abundances.abundance  as abundance 
                            from tbl_datasets
                            left join tbl_analysis_entities 
                               on tbl_datasets.dataset_id=tbl_analysis_entities.dataset_id
                             left join tbl_abundances 
                             on tbl_abundances.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
                            left join  tbl_physical_samples 
                               on tbl_analysis_entities.physical_sample_id=tbl_physical_samples.physical_sample_id
                            left join  tbl_sample_groups
                               on tbl_physical_samples.sample_group_id=tbl_sample_groups.sample_group_id
                            left join tbl_analysis_entity_prep_methods 
                              on tbl_analysis_entity_prep_methods.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
                            left  join tbl_methods 
                              on tbl_analysis_entity_prep_methods.method_id=tbl_methods.method_id
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
                           tbl_datasets.method_id=$method_id  and 
                           $filter
                           $facet_filtered

           ) as t
           group by species  ";
        return $q;
    }

    
    /**
     * Function arrange_species_data
     * Arrange abundance report on sample group level
     * First get all sample id and store those in a list.
     * Then get all abundances arranged by sample_id, using array_agg function in postgres
     * Then use those two list to make a transposed matrix
     * params: 
     *  $conn - db connections
     *  $sample_group_id  -
     *  $cache_id - reference to filters set by users stored as xml
     * 
     * returns:
     *  matrix of data as array
     * 
     * see also:
     * <get_f_code_filter_query>
     */
    function arrange_species_data($conn, $site_id, $method_id,$cache_id) {

        $q = $this->get_physical_sample_id_query($site_id,$method_id, $cache_id); // echo SqlFormatter::format($q,true);
        $rs = $this->run_query($conn, $q);

        while ($row = pg_fetch_assoc($rs)) {
            $physical_samples[$row["physical_sample_id"]] = $row["sample_name"] . " "; //<BR>(".$row["physical_sample_id"].")";
            $sample_groups[$row["physical_sample_id"]] = $row["sample_group_id"] . " "; //<BR>(".$row["physical_sample_id"].")";
        }

        $q = $this->render_species_count_query($site_id, $method_id,$cache_id); // echo SqlFormatter::format($q,true);
        $rs = $this->run_query($conn, $q);

        while ($row = pg_fetch_assoc($rs)) {
            $species_item = explode(";", $row["species_list_by_physical_sample_id"]);
            foreach ($species_item as $key => $element2) {
                // SAMPLE GROUP|ABUNDANCE 
                //      // Fallopia_convolvulus;7176|1;17628|2
                $parts = explode("|", $element2);
                $species_list[$row["species"]][$parts[0]] = $parts[1];
            }
        }

        if (isset($physical_samples)) {
            $data_array[0][0] = "Taxon || Physical sample";
            // $data_array[1][0] = "Taxon || Physical sample2";
            foreach ($physical_samples as $sample_id => $sample_name) {
                // header
               
                $data_array[0][$sample_id] = $sample_name."@".  $sample_groups[$sample_id]."";
             //   $data_array[1][$sample_id] = $sample_name;
            }
        }

        $row_count = 2;
        if (isset($species_list)) {
            foreach ($species_list as $specie => $physical_sample_abundance) {
                // $column_count = 0;
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

    
   public function get_physical_sample_id_query($site_id,$method_id, $cache_id) {

        global $facet_definition;
        $f_code = "physical_samples";
        if (!empty($cache_id)) {
            $facet_filtered = " and  " . $facet_definition[$f_code]["id_column"] . "  in (" . get_f_code_filter_query($cache_id, $f_code) . ") ";
        }
        $filter = !empty($site_id) ? " tbl_sample_groups.site_id = $site_id" : "1=1 ";

        $q = " select sample_name, tbl_sample_groups.sample_group_id,
                     tbl_physical_samples.physical_sample_id 
                    from tbl_physical_samples
                    join tbl_sample_groups
                        on tbl_sample_groups.sample_group_id=tbl_physical_samples.sample_group_id
                    join tbl_analysis_entities  
                      on tbl_analysis_entities.physical_sample_id=tbl_physical_samples.physical_sample_id
                    join tbl_datasets
                    on tbl_analysis_entities.dataset_id=tbl_datasets.dataset_id
                    join tbl_methods 
                on tbl_datasets.method_id=tbl_methods.method_id

                     where 
                     tbl_methods.method_id=$method_id  and 
                      $filter
                      $facet_filtered
                group by
                  sample_name,
                  tbl_sample_groups.sample_group_id,
                  tbl_physical_samples.physical_sample_id
                order by 
                  sample_name; ";

        return $q;
    } 
    
  function render_site_methods_query($site_id,$cache_id)
  {
       global $facet_definition;
        $f_code = "dataset_helper";
        if (!empty($cache_id)) {
            $facet_filtered = " and  " . $facet_definition[$f_code]["id_column"] . "  in (" . get_f_code_filter_query($cache_id, $f_code) . ") ";
        }
         $filter = !empty($site_id) ? "  tbl_sample_groups.site_id = $site_id" : "1=1 ";

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
         
  public function render_header_measured_values_query($site_id, $cache_id = null) {

        global $facet_definition;

        $filter = !empty($site_id) ? "  tbl_sample_groups.site_id = $site_id" : "1=1 ";

        $f_code = "sample_groups";
        if (!empty($cache_id)) {
            $facet_filtered = " and  " . $facet_definition[$f_code]["id_column"] . "  in (" . get_f_code_filter_query($cache_id, $f_code) . ") ";
        }
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

 /*
     * function: render_query_measured_values_by_sample_id
     * Get the query for a composite string object  with sample id and values of for different dataset/methods
     */

    function render_query_measured_values_by_sample_id($site_id, $cache_id = null) {
        global $facet_definition;
        $f_code = "result_facet";
        if (!empty($cache_id)) {
            $facet_filtered = " and  " . $facet_definition[$f_code]["id_column"] . "  in (" . get_f_code_filter_query($cache_id, $f_code) . ") ";
        }

        $filter = !empty($site_id) ? "  tbl_sample_groups.site_id = $site_id" : "1=1 ";
        $q = "  SELECT 
                        tbl_physical_samples.physical_sample_id ,
                        tbl_sample_groups.sample_group_id,
                        tbl_physical_samples.sample_name, 
                        array_to_string(array_agg(dm.method_id::text||'_'||COALESCE(aepm.method_id::text,'NULL')::text||'|'||to_char(tbl_measured_values.measured_value, '99999999.999')),';') as dataset_value_composites
                        FROM tbl_datasets
                            left join tbl_analysis_entities 
                              on tbl_datasets.dataset_id=tbl_analysis_entities.dataset_id
                            left join tbl_analysis_entity_prep_methods 
                              on tbl_analysis_entity_prep_methods.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
                            left join  tbl_physical_samples 
                              on tbl_analysis_entities.physical_sample_id=tbl_physical_samples.physical_sample_id
                            left join  tbl_sample_groups
                              on tbl_sample_groups.sample_group_id=tbl_physical_samples.sample_group_id
                            left    join tbl_methods dm 
                              on tbl_datasets.method_id=dm.method_id
                            left join tbl_methods aepm 
                              on tbl_analysis_entity_prep_methods.method_id=aepm.method_id
                            inner join tbl_measured_values 
                              on tbl_measured_values.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
                       WHERE
                          $filter 
                          $facet_filtered
                       GROUP BY
                         tbl_physical_samples.physical_sample_id ,
                         tbl_sample_groups.sample_group_id,
                         
                         tbl_physical_samples.sample_name";
        ;

        return $q;
    }

  public function arrange_measured_values($conn, $site_id, $cache_id){
      // get all dataset names as headings and dataset_id as key in array
        global $facet_definition;
        $q = $this->render_header_measured_values_query($site_id, $cache_id);
       
        $rs = $this->run_query($conn, $q);

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

        $q = $this->render_query_measured_values_by_sample_id($site_id, $cache_id);
        $rs = $this->run_query($conn, $q);

        while ($row = pg_fetch_assoc($rs)) {
            $sample_items = explode(";", $row["dataset_value_composites"]);
            foreach ($sample_items as $sample_key => $element2) {
                // SAMPLE GROUP|ABUNDANCE 
                //      // 1740|5.4000000000;396|52.0000000000;1263|18.0000000000;1661|18.0000000000;1263|216.0000000000
                $parts = explode("|", $element2);
                // echo $parts[0]; //part[0]= dataset_id
                $values_list_by_sample_and_dataset[$row["physical_sample_id"]][$parts[0]] = $parts[1];
                $sample_names[$row["physical_sample_id"]] = $row["sample_name"]."@".$row["sample_group_id"];
            }
        }
        $row_count = 1;
        if (isset($values_list_by_sample_and_dataset)) {
            foreach ($values_list_by_sample_and_dataset as $sample_key => $measured_values) {
                // $column_count = 0;
                //print_r($measured_values);
                $data_array[$row_count][$sample_key] = $sample_names[$sample_key]; // $sample_key;// sample_name
                foreach ($data_set_list as $dataset_id => $dataset_name) {
                    // lookup if exist and store empty or a value
                    $data_array[$row_count][$dataset_id] = $measured_values[$dataset_id];
                }
                $row_count++;
            }
        }
        return $data_array;
    }
      
  
  
    /*
     * function: get_sample_group_info_query
     * 
     * params: 
     * $site_id - id
     * $cache_id - filter reference 
     * 
     * see also:
     * <get_f_code_filter_query>
     */

    public function get_sample_group_info_query($site_id, $cache_id = null) {

        global $facet_definition;
        $f_code = "sample_groups";
        if (!empty($cache_id)) {
            $facet_filtered = " and  " . $facet_definition[$f_code]["id_column"] . "  in (" . get_f_code_filter_query($cache_id, $f_code) . ") ";
        }
        $filter = !empty($site_id) ? "tbl_sample_groups.site_id = $site_id" : "1=1 ";


        $q = "select 
                tbl_sample_groups.site_id as \"site id\",
                tbl_sample_groups.sample_group_id,
                sample_group_name as \"Sample group name\", 
                sampling_context as \"Sampling context\",
                method_name as \"Method name\" ,
                count(tbl_physical_samples.physical_sample_id) as \"Number of samples\"
               , array_to_string(array_agg(distinct tbl_datasets.dataset_id),',') as \"Datasets id\"
                from tbl_sample_groups
                inner join
                tbl_sample_group_sampling_contexts 
                  on tbl_sample_group_sampling_contexts.sampling_context_id=tbl_sample_groups.sampling_context_id
                inner join tbl_methods on tbl_sample_groups.method_id=tbl_methods.method_id
                inner join tbl_physical_samples on tbl_sample_groups.sample_group_id=tbl_physical_samples.sample_group_id
                inner  join tbl_analysis_entities on tbl_physical_samples.physical_sample_id=tbl_analysis_entities.physical_sample_id
                inner join tbl_datasets on tbl_datasets.dataset_id=tbl_analysis_entities.dataset_id
                where 
                $filter
                    $facet_filtered
                group by 
                  sample_group_name, 
                  tbl_sample_groups.sample_group_id, 
                  sampling_context, 
                  method_name
                order by 
                  tbl_sample_groups.site_id

                ";

        return $q;
    }

    /*
     * function : get_dataset_query
     * makes query for basic dataset info for a site or set of sites
     * using cache_id for filter from facets
     * or site_id if it only one single site.
     * 
     * see also:
     * <get_f_code_filter_query>
     */

    public function get_dataset_query($site_id, $cache_id) {
        global $facet_definition;

        if (!empty($cache_id)) {
            $f_code = "dataset_helper";
            $q_analysis_entities_query = " and  " . $facet_definition[$f_code]["id_column"] . "  in (" . get_f_code_filter_query($cache_id, $f_code) . ") ";
        }

        $filter = !empty($site_id) ? "tbl_sites.site_id = $site_id" : "1=1 ";

        $q = " SELECT  
                     tbl_sites.site_id as \"Site id\",
                     tbl_sample_groups.sample_group_id as \"Sample group id\",
                     tbl_datasets.dataset_id as \"Dataset id\",
                     tbl_datasets.dataset_name as \"Dataset name\",
                     tbl_methods.method_name as \"Method name\", 
                     aepm.method_name as \"Preparation method\",
                     tbl_methods.method_abbrev_or_alt_name as \"Method abbrevation\",
                     tbl_methods.description \"Method description\",
                     tbl_record_types.record_type_name as \"Record type\"
                FROM tbl_datasets
                left  join tbl_analysis_entities
                    on tbl_analysis_entities.dataset_id = tbl_datasets.dataset_id
                left join tbl_analysis_entity_prep_methods 
                    on tbl_analysis_entity_prep_methods.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
                left     join tbl_methods 
                    on tbl_datasets.method_id=tbl_methods.method_id
                left  join tbl_record_types 
                    on tbl_methods.record_type_id = tbl_record_types.record_type_id
                join tbl_physical_samples
                    on tbl_analysis_entities.physical_sample_id = tbl_physical_samples.physical_sample_id
                left     join tbl_methods aepm 
                    on tbl_analysis_entity_prep_methods.method_id=aepm.method_id
                left  join tbl_sample_groups
                    on tbl_physical_samples.sample_group_id = tbl_sample_groups.sample_group_id
                inner  join tbl_sites
                    on tbl_sample_groups.site_id = tbl_sites.site_id
                WHERE 
                $filter 
                 $q_analysis_entities_query  
                GROUP BY 
                 tbl_datasets.dataset_id,
                 tbl_datasets.dataset_name,
                 tbl_methods.method_name, 
                 tbl_methods.method_abbrev_or_alt_name,
                 tbl_methods.description,
                 tbl_record_types.record_type_name,
                 tbl_sample_groups.sample_group_id,
                 tbl_sites.site_id ,
                 aepm.method_name
               ORDER BY 
                  tbl_sites.site_id";
        return $q;
    }

}

?>