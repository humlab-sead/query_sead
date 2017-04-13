<?php

/* 
file: facet_definitions.php (SEAD)

o The definition of the facets

definition of facets:
 id_column - definition of which column in a table which should be unique for the facet,
			table name should be included eg bastab."GEOID"
name_column - Definition of which column should be shown in the facet eg "bastab1.\"GEOIDNMN\"",
display_title - Title of the facet,
applicable  - 0 or 1 which defines if the facet should be used or not
icon_id_column - not used
sort_column - column for sorting items in facet
facet_type - type of facet eg discrete, range or geo
table - table in which the information exist, used when joining tables togehter and used in query-builder
query_cond_table - additional table could be added so that list of tables will be join when quering for the facet content
query_cond - conditions can be added as static condition in order to create a subset of data for a particular table/column
default -  0 or 1 which indicates if it should be loaded automatically.
color - color of the facet (not used )
category - catergory of facet,could be used for grouping facet in control-bar
parents - under what heading should the facet appear

*/

$facet_definition = array
(

    "others" =>
        array(
            "display_title" => "Others",
            "applicable" => "1",
            "facet_type" => "title",
            "default" => 0,
            "color" => "ff0000",
            "parents" => array("ROOT"),
        ),

    "space_time" =>
        array(
            "display_title" => "Space/Time",
            "applicable" => "1",
            "facet_type" => "title",
            "default" => 0,
            "color" => "ff0000",
            "parents" => array("ROOT"),
        ),
    "time" =>
        array(
            "display_title" => "Time",
            "applicable" => "1",
            "facet_type" => "title",
            "default" => 0,
            "color" => "ff0000",
            "parents" => array("ROOT"),
        ),

    "ecology" =>
        array(
            "display_title" => "Ecology",
            "applicable" => "1",
            "facet_type" => "title",
            "default" => 0,
            "color" => "ff0000",
            "parents" => array("ROOT"),
        ),
    "measured_values" =>
        array(
            "display_title" => "Measured values",
            "applicable" => "1",
            "facet_type" => "title",
            "default" => 0,
            "color" => "ff0000",
            "parents" => array("ROOT"),
        ),
    "taxonomy" =>
        array(
            "display_title" => "Taxonomy",
            "applicable" => "1",
            "facet_type" => "title",
            "default" => 0,
            "color" => "ff0000",
            "parents" => array("ROOT"),
        ),


    "result_facet" =>
        array(
            "id_column" => "tbl_analysis_entities.analysis_entity_id",
            "name_column" => "tbl_physical_samples.sample_name||' '||tbl_datasets.dataset_name",
            "display_title" => "Analysis entities",
            "applicable" => "0",
            "icon_id_column" => "tbl_analysis_entities.analysis_entity_id",
            "sort_column" => "tbl_datasets.dataset_name",
            "facet_type" => "discrete",
            "table" => "tbl_analysis_entities",
            "query_cond_table" => array("tbl_physical_samples", "tbl_datasets"),
            "query_cond" => "",
            "default" => 0,
            "parents" => array("ROOT") //$facet_category[0]
        ),

    "dataset_helper" =>
        array(
            "id_column" => "tbl_datasets.dataset_id",
            "name_column" => "tbl_datasets.dataset_id",
            "display_title" => "dataset_helper",
            "applicable" => "0",
            "icon_id_column" => "tbl_dataset.dataset_id",
            "sort_column" => "tbl_dataset.dataset_id",
            "facet_type" => "discrete",
            "table" => "tbl_datasets",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("ROOT") //$facet_category[0]
        ),


    "tbl_denormalized_measured_values_33_0" =>
        array(
            "id_column" => "metainformation.tbl_denormalized_measured_values.value_33_0",
            "name_column" => "metainformation.tbl_denormalized_measured_values.value_33_0",
            "display_title" => "MS ",
            "applicable" => "1",
            "icon_id_column" => "metainformation.tbl_denormalized_measured_values.value_33_0",
            "sort_column" => "metainformation.tbl_denormalized_measured_values.value_33_0",
            "facet_type" => "range",
            "table" => "metainformation.tbl_denormalized_measured_values",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("measured_values") //$facet_category[0]
        ),

    "tbl_denormalized_measured_values_33_82" =>
        array(
            "id_column" => "metainformation.tbl_denormalized_measured_values.value_33_82",
            "name_column" => "metainformation.tbl_denormalized_measured_values.value_33_82",
            "display_title" => "MS Heating 550",
            "applicable" => "1",
            "icon_id_column" => "metainformation.tbl_denormalized_measured_values.value_33_82",
            "sort_column" => "metainformation.tbl_denormalized_measured_values.value_33_82",
            "facet_type" => "range",
            "table" => "metainformation.tbl_denormalized_measured_values",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("measured_values") //$facet_category[0]
        ),

    "tbl_denormalized_measured_values_32" =>
        array(
            "id_column" => "metainformation.tbl_denormalized_measured_values.value_32_0",
            "name_column" => "metainformation.tbl_denormalized_measured_values.value_32_0",
            "display_title" => "LOI",
            "applicable" => "1",
            "icon_id_column" => "metainformation.tbl_denormalized_measured_values.value_32",
            "sort_column" => "metainformation.tbl_denormalized_measured_values.value_32",
            "facet_type" => "range",
            "table" => "metainformation.tbl_denormalized_measured_values",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("measured_values") //$facet_category[0]
        ),


    "tbl_denormalized_measured_values_37" =>
        array(
            "id_column" => "metainformation.tbl_denormalized_measured_values.value_37_0",
            "name_column" => "metainformation.tbl_denormalized_measured_values.value_37_0",
            "display_title" => " P°",
            "applicable" => "1",
            "icon_id_column" => "metainformation.tbl_denormalized_measured_values.value_37",
            "sort_column" => "metainformation.tbl_denormalized_measured_values.value_37",
            "facet_type" => "range",
            "table" => "metainformation.tbl_denormalized_measured_values",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("measured_values") //$facet_category[0]
        ),


    "measured_values_helper" =>
        array(
            "id_column" => "tbl_measured_values.measured_value",
            "name_column" => "tbl_measured_values.measured_value",
            "display_title" => "values",
            "applicable" => "0",
            "icon_id_column" => "tbl_measured_values.measured_value",
            "sort_column" => "tbl_measured_values.measured_value",
            "facet_type" => "discrete",
            "table" => "tbl_measured_values",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("ROOT") //$facet_category[0]
        ),

    "taxon_result" =>
        array(
            "id_column" => "tbl_abundances.taxon_id",
            "name_column" => "tbl_abundances.taxon_id",
            "display_title" => "taxon_id",
            "applicable" => "0",
            "icon_id_column" => "tbl_abundances.taxon_id",
            "sort_column" => "tbl_abundances.taxon_id",
            "facet_type" => "discrete",
            "table" => "tbl_abundances",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("ROOT") //$facet_category[0]
        ),

    "map_result" =>
        array(
            "id_column" => "tbl_sites.site_id",
            "name_column" => "tbl_sites.site_name",
            "display_title" => "Site",
            "applicable" => "0",
            "icon_id_column" => "tbl_sites.site_id",
            "sort_column" => "tbl_sites.site_name",
            "facet_type" => "discrete",
            "table" => "tbl_sites",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("ROOT") //$facet_category[0]
        ),

    // Proxy types (on the site)
    "geochronology" =>
        array(
            "id_column" => "tbl_geochronology.age",
            "name_column" => "tbl_geochronology.age",
            "display_title" => "Geochronology",
            "applicable" => "1",
            "icon_id_column" => "tbl_geochronology.age",
            "sort_column" => "tbl_geochronology.age",
            "facet_type" => "range",
            "table" => "tbl_geochronology",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("time") //$facet_category[4]
        ),

    "relative_age_name" =>
        array(
            "id_column" => "tbl_relative_ages.relative_age_id",
            "name_column" => "tbl_relative_ages.relative_age_name",
            "display_title" => "Time periods",
            "applicable" => "1",
            "icon_id_column" => "tbl_relative_ages.relative_age_id",
            "sort_column" => "tbl_relative_ages.relative_age_name",
            "facet_type" => "discrete",
            "table" => "tbl_relative_ages",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("time") //$facet_category[4]
        ),


    "record_types" =>
        array(
            "id_column" => "tbl_record_types.record_type_id",
            "name_column" => "tbl_record_types.record_type_name",
            "display_title" => "Proxy types",
            "applicable" => "1",
            "icon_id_column" => "tbl_record_types.record_type_id",
            "sort_column" => "tbl_record_types.record_type_name",
            "facet_type" => "discrete",
            "table" => "tbl_record_types",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("others") //$facet_category[4]
        ),


    "sample_groups" =>
        array(
            "id_column" => "tbl_sample_groups.sample_group_id",
            "name_column" => "tbl_sample_groups.sample_group_name",
            "display_title" => "Sample group",
            "applicable" => "1",
            "icon_id_column" => "tbl_sample_groups.sample_group_id",
            "sort_column" => "tbl_sample_groups.sample_group_name",
            "facet_type" => "discrete",
            "table" => "tbl_sample_groups",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 1,
            "parents" => array("space_time") //$facet_category[3]
        ),
    "places" =>
        array(
            "id_column" => "tbl_locations.location_id",
            "name_column" => "tbl_locations.location_name",
            "display_title" => "Places",
            "applicable" => "0",
            "icon_id_column" => "tbl_locations.location_id",
            "sort_column" => "tbl_locations.location_name",
            "facet_type" => "discrete",
            "table" => "tbl_locations",
            "query_cond_table" => array("tbl_site_locations"),
            "query_cond" => "",
            "default" => 1,
            "parents" => array("space_time") //$facet_category[3]
        ),
    "places_all2" =>
        array(
            "id_column" => "tbl_locations.location_id",
            "name_column" => "tbl_locations.location_name",
            "display_title" => "view_places_relations",
            "applicable" => "0",
            "icon_id_column" => "view_places_relations.rel_id",
            "sort_column" => "tbl_locations.location_name",
            "facet_type" => "discrete",
            "table" => "view_places_relations",
            "query_cond_table" => array("tbl_locations", "tbl_site_locations"),
            "query_cond" => "",
            "default" => 1,
            "parents" => array("space_time") //$facet_category[3]
        ),


    "sample_groups_helper" =>
        array(
            "id_column" => "tbl_sample_groups.sample_group_id",
            "name_column" => "tbl_sample_groups.sample_group_name",
            "display_title" => "Sample group",
            "applicable" => "0",
            "icon_id_column" => "tbl_sample_groups.sample_group_id",
            "sort_column" => "tbl_sample_groups.sample_group_name",
            "facet_type" => "discrete",
            "table" => "tbl_sample_groups",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 1,
            "parents" => array("space_time") //$facet_category[3]
        ),


    "physical_samples" =>
        array(
            "id_column" => "tbl_physical_samples.physical_sample_id",
            "name_column" => "tbl_physical_samples.sample_name",
            "display_title" => "physical samples",
            "applicable" => "0",
            "icon_id_column" => "tbl_physical_samples.physical_sample_id",
            "sort_column" => "tbl_physical_samples.sample_name",
            "facet_type" => "discrete",
            "table" => "tbl_physical_samples",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 1,
            "parents" => array("space_time") //$facet_category[3]
        ),
    //Site
    "sites" =>
        array(
            "id_column" => "tbl_sites.site_id",
            "name_column" => "tbl_sites.site_name",
            "display_title" => "Site",
            "applicable" => "1",
            "icon_id_column" => "tbl_sites.site_id",
            "sort_column" => "tbl_sites.site_name",
            "facet_type" => "discrete",
            "table" => "tbl_sites",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 1,
            "parents" => array("space_time") //$facet_category[3]
        ),
    //Site
    "sites_helper" =>
        array(
            "id_column" => "tbl_sites.site_id",
            "name_column" => "tbl_sites.site_name",
            "display_title" => "Site",
            "applicable" => "0",
            "icon_id_column" => "tbl_sites.site_id",
            "sort_column" => "tbl_sites.site_name",
            "facet_type" => "discrete",
            "table" => "tbl_sites",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 1,
            "parents" => array("space_time") //$facet_category[3]
        ),

    "tbl_relative_dates_helper" =>
        array(
            "id_column" => "tbl_relative_dates.relative_age_id",
            "name_column" => "tbl_relative_dates.relative_age_name ",
            "display_title" => "tbl_relative_dates",
            "applicable" => "0",
            "icon_id_column" => "tbl_relative_dates.relative_age_name",
            "sort_column" => "tbl_relative_dates.relative_age_name ",
            "facet_type" => "discrete",
            "table" => "tbl_relative_dates",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("time") //$facet_category[3]
        ),

    //Ecocode
    "ecocode" =>
        array(
            "id_column" => "tbl_ecocode_definitions.ecocode_definition_id",
            "name_column" => "tbl_ecocode_definitions.label",
            "display_title" => "Eco code",
            "applicable" => "1",
            "icon_id_column" => "tbl_ecocode_definitions.ecocode_definition_id",
            "sort_column" => "tbl_ecocode_definitions.label",
            "facet_type" => "discrete",
            "table" => "tbl_ecocode_definitions",
            "query_cond_table" => array("tbl_ecocode_definitions"),
            "query_cond" => "",
            "default" => 0,
            "parents" => array("ecology") //$facet_category[2]
        ),

    "family" =>
        array(
            "id_column" => "tbl_taxa_tree_families.family_id",
            "name_column" => "tbl_taxa_tree_families.family_name ",
            "display_title" => "Family",
            "applicable" => "1",
            "icon_id_column" => "tbl_taxa_tree_families.family_id",
            "sort_column" => "tbl_taxa_tree_families.family_name ",
            "facet_type" => "discrete",
            "table" => "tbl_taxa_tree_families",
            "query_cond_table" => array("tbl_taxa_tree_families"),
            "query_cond" => "",
            "default" => 0,
            "parents" => array("taxonomy") //$facet_category[1]
        ),

    "genus" =>
        array(
            "id_column" => "tbl_taxa_tree_genera.genus_id",
            "name_column" => "tbl_taxa_tree_genera.genus_name",
            "display_title" => "Genus",
            "applicable" => "1",
            "icon_id_column" => "tbl_taxa_tree_genera.genus_id",
            "sort_column" => "tbl_taxa_tree_genera.genus_name",
            "facet_type" => "discrete",
            "table" => "tbl_taxa_tree_genera",
            "query_cond_table" => array("tbl_taxa_tree_genera"),
            "query_cond" => "",
            "default" => 0,
            "parents" => array("taxonomy") //$facet_category[1]
        ),


    "species_helper" =>
        array(
            "id_column" => "tbl_taxa_tree_master.taxon_id",
            "name_column" => "tbl_taxa_tree_master.taxon_id",
            "display_title" => "Species",
            "applicable" => "0",
            "icon_id_column" => "tbl_taxa_tree_master.taxon_id",
            "sort_column" => "tbl_taxa_tree_master.species",
            "facet_type" => "discrete",
            "table" => "tbl_taxa_tree_master",
            "query_cond_table" => array("tbl_taxa_tree_genera", "tbl_taxa_tree_authors"),
            "query_cond" => "",
            "default" => 0,
            "parents" => array("taxonomy") //$facet_category[1]
        ),

    "abundance_helper" =>
        array(
            "id_column" => "tbl_abundances.abundance_id",
            "name_column" => "tbl_abundances.abundance_id",
            "display_title" => "abundance_id",
            "applicable" => "0",
            "icon_id_column" => "tbl_abundances.abundance_id",
            "sort_column" => "tbl_abundances.abundance_id",
            "facet_type" => "discrete",
            "table" => "tbl_abundances",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("taxonomy") //$facet_category[1]
        ),


    "species_author" =>
        array(
            "id_column" => "tbl_taxa_tree_authors.author_id ",
            "name_column" => "tbl_taxa_tree_authors.author_name ",
            "display_title" => "Author",
            "applicable" => "1",
            "icon_id_column" => "tbl_taxa_tree_authors.author_id ",
            "sort_column" => "tbl_taxa_tree_authors.author_name ",
            "facet_type" => "discrete",
            "table" => "tbl_taxa_tree_authors",
            "query_cond_table" => array("tbl_taxa_tree_authors"),
            "query_cond" => "",
            "default" => 0,
            "parents" => array("taxonomy") //$facet_category[1]
        ),


    //TODO:  Rdb code (eco-code?)
    "feature_type" =>
        array(
            "id_column" => "tbl_feature_types.feature_type_id ",
            "name_column" => "tbl_feature_types.feature_type_name",
            "display_title" => "Feature type",
            "applicable" => "1",
            "icon_id_column" => "tbl_feature_types.feature_id ",
            "sort_column" => "tbl_feature_types.feature_type_name",
            "facet_type" => "discrete",
            "table" => "tbl_feature_types",
            "query_cond_table" => array("tbl_physical_sample_features"),
            "query_cond" => "",
            "default" => 0,
            "parents" => array("others")  //$facet_category[2]
        ),
    "ecocode_system" =>
        array(
            "id_column" => "tbl_ecocode_systems.ecocode_system_id ",
            "name_column" => "tbl_ecocode_systems.name",
            "display_title" => "Eco code system",
            "applicable" => "1",
            "icon_id_column" => "tbl_ecocode_systems.ecocode_system_id ",
            "sort_column" => "tbl_ecocode_systems.definition",
            "facet_type" => "discrete",
            "table" => "tbl_ecocode_systems",
            "query_cond_table" => array("tbl_ecocode_systems"),
            "query_cond" => "",
            "default" => 0,
            "parents" => array("ecology") //$facet_category[2]
        ),

    "abundance_classification" =>
        array(
            "id_column" => "metainformation.view_abundance.elements_part_mod ",
            "name_column" => "metainformation.view_abundance.elements_part_mod ",
            "display_title" => "abundance classification",
            "applicable" => "1",
            "icon_id_column" => "metainformation.view_abundance.elements_part_mod ",
            "sort_column" => "metainformation.view_abundance.elements_part_mod ",
            "facet_type" => "discrete",
            "table" => "metainformation.view_abundance",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("ecology")  //$facet_category[2]
        ),

    "abundances_all_helper" =>
        array(
            "id_column" => "metainformation.view_abundance.abundance ",
            "name_column" => "metainformation.view_abundance.abundance ",
            "display_title" => "Abundances",
            "applicable" => "0",
            "icon_id_column" => "metainformation.view_abundance.abundance ",
            "sort_column" => "metainformation.view_abundance.abundance",
            "facet_type" => "range",
            "table" => "metainformation.view_abundance",
            "query_cond_table" => "",
            "query_cond" => "metainformation.view_abundance.abundance is not null",
            "default" => 0,
            "parents" => array("ecology")  //$facet_category[2]
        ),
    "abundances_all" =>
        array(
            "id_column" => "metainformation.view_abundance.abundance ",
            "name_column" => "metainformation.view_abundance.abundance ",
            "display_title" => "Abundances",
            "applicable" => "1",
            "icon_id_column" => "metainformation.view_abundance.abundance ",
            "sort_column" => "metainformation.view_abundance.abundance",
            "facet_type" => "range",
            "table" => "metainformation.view_abundance",
            "query_cond_table" => "",
            "query_cond" => "metainformation.view_abundance.abundance is not null",
            "default" => 0,
            "parents" => array("ecology")  //$facet_category[2]
        ),


    //Eco code system


    "activeseason" =>
        array(
            "id_column" => "tbl_seasons.season_id",
            "name_column" => "tbl_seasons.season_name ",
            "display_title" => "Seasons",
            "applicable" => "1",
            "icon_id_column" => "tbl_seasons.season_id",
            "sort_column" => "tbl_seasons.season_type ",
            "facet_type" => "discrete",
            "table" => "tbl_seasons",
            "query_cond_table" => "",
            "query_cond" => "",
            "default" => 0,
            "parents" => array("time") //$facet_category[1]
        ),
    "tbl_biblio_sample_groups" =>
        array(
            "id_column" => "tbl_biblio.biblio_id",
            "name_column" => "tbl_biblio.title||'  '||tbl_biblio.author",
            "display_title" => "Bibligraphy sites/Samplegroups",
            "applicable" => "1",
            "icon_id_column" => "tbl_biblio.biblio_id",
            "sort_column" => "tbl_biblio.author",
            "facet_type" => "discrete",
            "table" => "tbl_biblio",
            "query_cond_table" => array("metainformation.view_sample_group_references"),
            "default" => 0,
            "query_cond" => "metainformation.view_sample_group_references.biblio_id is not null",
            "parents" => array("others") //$facet_category[1]
        ),

    "tbl_biblio_sites" =>
        array(
            "id_column" => "tbl_biblio.biblio_id",
            "name_column" => "tbl_biblio.title||'  '||tbl_biblio.author",
            "display_title" => "Bibligraphy sites",
            "applicable" => "0",
            "icon_id_column" => "tbl_biblio.biblio_id",
            "sort_column" => "tbl_biblio.author",
            "facet_type" => "discrete",
            "table" => "tbl_biblio",
            "query_cond_table" => array("metainformation.view_site_references"),
            "default" => 0,
            "query_cond" => "metainformation.view_site_references.biblio_id is not null",
            "parents" => array("others") //$facet_category[1]
        ),

    "country" =>
        array(
            "id_column" => "countries.location_id",
            "name_column" => "countries.location_name ",
            "display_title" => "Country",
            "applicable" => "1",
            "icon_id_column" => "countries.location_id",
            "sort_column" => "countries.location_name",
            "facet_type" => "discrete",
            "table" => "tbl_locations",
            "query_cond_table" => array("tbl_site_locations"),
            "query_cond" => "countries.location_type_id=1",
            "default" => 0,
            "parents" => array("space_time"), //$facet_category[3]
            "alias_table" => "countries"
        ),

    "species" =>
        array(
            "id_column" => "tbl_taxa_tree_master.taxon_id",
            "name_column" => "COALESCE(tbl_taxa_tree_genera.genus_name,'')||' '||COALESCE(tbl_taxa_tree_master.species,'')||' '||COALESCE(' '||tbl_taxa_tree_authors.author_name||' ','')",
            "display_title" => "Taxa",
            "applicable" => "1",
            "icon_id_column" => "tbl_taxa_tree_master.taxon_id",
            "sort_column" => "tbl_taxa_tree_genera.genus_name||' '||tbl_taxa_tree_master.species",
            "facet_type" => "discrete",
            "table" => "tbl_taxa_tree_master",
            "query_cond_table" => array("tbl_taxa_tree_genera", "tbl_taxa_tree_authors", "tbl_sites"),
            "query_cond" => " tbl_sites.site_id is not null",
            "default" => 0,
            "parents" => array("taxonomy"), //$facet_category[1]
            "count_facet" => "abundances_all_helper",
            "counting_title" => "sum of Abundance",
            "summarize_type" => "sum",
        ),

    "tbl_biblio_modern" =>
        array(
            "id_column" => "metainformation.view_taxa_biblio.biblio_id",
            "name_column" => "tbl_biblio.title||'  '||tbl_biblio.author ",
            "display_title" => "Bibligraphy modern",
            "applicable" => "1",
            "icon_id_column" => "tbl_biblio.biblio_id",
            "sort_column" => "tbl_biblio.author",
            "facet_type" => "discrete",
            "count_facet" => "species_helper",
            "counting_title" => "count of species",
            "summarize_type" => "count",
            "table" => "metainformation.view_taxa_biblio",
            "query_cond_table" => array("tbl_biblio"),
            "default" => 0,
            "query_cond" => "",
            "parents" => array("others") //$facet_category[1]
        )
);

// $facet_keys = [];
// foreach ($facet_definition as $facet) {
//     foreach (array_keys($facet) as $key)
//         $facet_keys[$key] = $key;
// }

// print($facet_keys);


class FacetDefinition
{
    public $facet_code = "";
    public $facet_type;

    public $table;
    public $alias_table;
    public $id_column;
    public $name_column;
    public $query_cond_table = [];
    public $query_cond;
    public $sort_column;

    public $display_title = "";
    public $applicable = "0";
    public $default = 0;
    public $count_facet;
    public $counting_title;
    public $summarize_type;
    public $parents = [];

    /* Client properties */
    public $color;
    public $icon_id_column;

    /* Computed properties */
    public $table_or_alias;
    public $sql_sort_clause;

    function __construct($facet_code, $property_array) {
        $this->facet_code = $facet_code;
        foreach ($property_array as $property => $value) {
            $this->$property = $value;
        }
        // derived properties
        $this->table_or_alias = $this->alias_table ?? $this->table;
        $this->sql_sort_clause = empty($this->sort_column) ? "" : "ORDER BY {$this->sort_column} {$this->sort_order}";
    }

    public function isOfType($type)
    {
        return $this->facet_type == $type;
    }

    public function isOfTypeRange()
    {
        return $this->facet_type == "range";
    }

    // function camelize($input, $separator = '_')
    // {
    //     return str_replace($separator, '', ucwords($input, $separator));
    // }
}

// FIXME: Wrap all global variables within a registry
class FacetRegistry 
{
    public static $facetDefinitions = NULL;

    public static function getDefinitions()
    {
        global $facet_definition;
        if (self::$facetDefinitions == NULL) {
            self::$facetDefinitions = [];
            foreach ($facet_definition as $key => $item) {
                self::$facetDefinitions[$key] = new FacetDefinition($key, $item);
            }
        }
        return self::$facetDefinitions;
    }

    public static function getDefinition($facetCode)
    {
        return self::getDefinitions()[$facetCode];
    }
}
?>