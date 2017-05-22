INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('sitename', 'tbl_sites', 'tbl_sites.site_name', 'Site name', 'single_item', '1', 'Array', '', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('record_type', 'tbl_record_types', 'tbl_record_types.record_type_name', 'Record type(s)', 'text_agg_item', '1', 'Array', '', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('analysis_entities', 'tbl_analysis_entities', 'tbl_analysis_entities.analysis_entity_id', 'Filtered records', 'single_item', '1', 'Array', '', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('site_link', 'tbl_sites', 'tbl_sites.site_id', 'Full report', 'link_item', '1', 'Array', 'api/report/show_site_details.php?site_id', 'Show site report')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('site_link_filtered', 'tbl_sites', 'tbl_sites.site_id', 'Filtered report', 'link_item', '1', 'Array', 'api/report/show_site_details.php?site_id', 'Show filtered report')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('aggregate_all_filtered', 'tbl_aggregate_samples', '''Aggregated''::text', 'Filtered report', 'link_item_filtered', '1', 'Array', 'api/report/show_details_all_levels.php?level', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('sample_group_link', 'tbl_sample_groups', 'tbl_sample_groups.sample_group_id', 'Full report', 'link_item', '1', 'Array', 'api/report/show_sample_group_details.php?sample_group_id', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('sample_group_link_filtered', 'tbl_sample_groups', 'tbl_sample_groups.sample_group_id', 'Filtered report', 'link_item', '1', 'Array', 'api/report/show_sample_group_details.php?sample_group_id', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('abundance', 'tbl_abundances', ' tbl_abundances.abundance', 'number of taxon_id', 'single_item', '1', 'Array', '', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('taxon_id', 'tbl_abundances', ' tbl_abundances.taxon_id', 'Taxon id  (specie)', 'single_item', '1', 'Array', '', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('dataset', 'tbl_datasets', 'tbl_datasets.dataset_name', 'Dataset', 'single_item', '1', 'Array', '', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('dataset_link', 'tbl_datasets', 'tbl_datasets.dataset_id', 'Dataset details', 'single_item', '1', 'Array', 'client/show_dataset_details.php?dataset_id', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('dataset_link_filtered', 'tbl_datasets', 'tbl_datasets.dataset_id', 'Filtered report', 'single_item', '1', 'Array', 'client/show_dataset_details.php?dataset_id', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('sample_group', 'tbl_sample_groups', 'tbl_sample_groups.sample_group_name', 'Sample group', 'single_item', '1', 'Array', '', '')
INSERT INTO facet.tbl_result_fields (result_field_key,table_name,column_name,display_text,result_type,activated,parents,link_url,link_label) VALUES ('methods', 'tbl_methods', 'tbl_methods.method_name', 'Method', 'single_item', '1', 'Array', '', '')
