<?php

require __DIR__ . '/../../server/fb_server_funct.php';

function run_query($conn, $q) {
    if (($rs = pg_query($conn, $q)) <= 0) {
        echo "Error: cannot execute site query. " . SqlFormatter::format($q,false) . " \n";
        exit;
    }

    return $rs;
}

$conn = ConnectionHelper::createConnection();

$q_methods="
 select tbl_datasets.method_id as dataset_method, COALESCE( tbl_analysis_entity_prep_methods.method_id::text,'0'::text) as prep_method,  count(*)
 from tbl_analysis_entities
 left join tbl_analysis_entity_prep_methods
   on  tbl_analysis_entity_prep_methods.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
 join tbl_datasets
   on tbl_analysis_entities.dataset_id=tbl_datasets.dataset_id
 where tbl_datasets.method_id is not null
 group by dataset_method,prep_method
 order by dataset_method";

/*
*
SELECT
tbl_physical_samples.physical_sample_id
*
* sum (value_xx_Xx)
* count value_xx_xx)
*
* -----
* --- etc
*
*
*  group by tbl_physical_samples.physical_sample_id
order by
tbl_physical_samples.physical_sample_id
*/

$rs = run_query($conn, $q_methods);

$counter=0;

while ($row = pg_fetch_assoc($rs)) {
    $build_view_selects[]=" max( values_".$row["dataset_method"]."_".$row["prep_method"]. ".measured_value)  as value_".$row["dataset_method"]."_".$row["prep_method"];
    
    $build_view_joins[$counter]="  LEFT JOIN (SELECT tbl_measured_values.measured_value,
    tbl_measured_values.analysis_entity_id
    FROM   tbl_measured_values
    JOIN tbl_analysis_entities
    ON tbl_measured_values.analysis_entity_id =
    tbl_analysis_entities.analysis_entity_id
    JOIN tbl_datasets
    ON tbl_datasets.dataset_id =
    tbl_analysis_entities.dataset_id
    left join tbl_analysis_entity_prep_methods
    on  tbl_analysis_entity_prep_methods.analysis_entity_id=tbl_analysis_entities.analysis_entity_id
    
    WHERE  tbl_datasets.method_id = ".$row["dataset_method"].
    " and   COALESCE( tbl_analysis_entity_prep_methods.method_id,0) = ".$row["prep_method"]."   )
    AS values_".$row["dataset_method"]."_".$row["prep_method"] . "
    ON tbl_analysis_entities.analysis_entity_id =values_".$row["dataset_method"]."_".$row["prep_method"].".analysis_entity_id  ";
    
    
    $having[]=" count_".$row["dataset_method"]."_".$row["prep_method"] .">1";
    $counter++;
    
}

$q="select tbl_physical_samples.physical_sample_id,";
$q.="\n ". implode(",",$build_view_selects);
$q.=" from tbl_analysis_entities
 join tbl_physical_samples
 on tbl_physical_samples.physical_sample_id=tbl_analysis_entities.physical_sample_id";

$q.=implode("\n -- next \n ",$build_view_joins);
$q.=" group by tbl_physical_samples.physical_sample_id
order by
tbl_physical_samples.physical_sample_id";
echo SqlFormatter::format($q,false);
?>