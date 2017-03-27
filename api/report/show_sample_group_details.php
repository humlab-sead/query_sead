<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>

<HEAD>
  <TITLE>Details of SEAD sample group</TITLE>
  <META NAME="Generator" CONTENT="Netbeans">
  <META NAME="Author" CONTENT="SEAD">
  <META NAME="Keywords" CONTENT="sead">
  <META NAME="Description" CONTENT="Details of SEAD dataset">
  <meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />

  <!-- DataTables -->
  <link rel="stylesheet" href="//cdn.datatables.net/1.9.4/css/jquery.dataTables.css">
  <link rel="stylesheet" href="//cdn.datatables.net/tabletools/2.1.5/css/TableTools.css">
  <link rel="stylesheet" href="/client/theme/reporting.css">
  <script type="text/javascript" charset="utf-8" language="javascript" src="//code.jquery.com/jquery-1.8.2.min.js"></script>
  <script type="text/javascript" charset="utf-8" language="javascript" src="//cdn.datatables.net/1.9.4/js/jquery.dataTables.min.js"></script>
  <script type="text/javascript" charset="utf-8" language="javascript" src="//cdn.datatables.net/tabletools/2.1.5/js/TableTools.min.js"></script>

  <!-- DataTables -->
  <script type="text/javascript" charset="utf-8">
    /* Table initialisation */
    $(document).ready(function() {
      $("table[id|='d_table']").each(function() {
        $(this).dataTable({
          "bPaginate": false, // Turned pagination off
          "oTableTools": {
            "sSwfPath": "//cdn.datatables.net/tabletools/2.1.5/swf/copy_csv_xls_pdf.swf",
            "aButtons": ["copy", "csv", "pdf", "print"]
          },
          "sDom": "T<'row'<'span6'l><'span6'f>r>t<'row'<'span6'i><'span6'p>>", // table setup

          "bInfo": false, // Turned info off
          "oLanguage": {
            "sLengthMenu": "_MENU_ records per page",
            "sSearch": "Filter results: _INPUT_" // Renamed Search to Filter
          },
        });
      });
    });
  </script>

</HEAD>

<BODY>

  <?php

/*
* file: show_sample_group_details.php
* Make a report for a set of sample_gruops
*
* see also:
* - <sample_group_query>
* - <report_module.php>
*
* uses:
* - <sample_group_reporter->dataset_report>
* - <sample_group_reporter->species_report>
* - <sample_group_reporter->measured_values_report>
*
*
*/
require_once __DIR__ . '/sample_group_queries.php';
require_once __DIR__ . '/report_module.php';

if (isset($_REQUEST["sample_group_id"]) && !empty($_REQUEST["sample_group_id"]) && is_numeric($_REQUEST["sample_group_id"])) {
    $sample_group_id = $_REQUEST["sample_group_id"];
} else {
    echo "No sample_group_id specified.";
    exit;
}

$cache_id = $_REQUEST["cache_id"];

if (!($conn = pg_connect(CONNECTION_STRING))) {
    echo "Error: pg_connect failed.\n";
    exit;
}

$sample_group_reporter = new sample_group_reporter();
if (!empty($cache_id)) {
    echo get_select_info_as_html($conn, $cache_id);
}

echo $sample_group_reporter->sample_summary($conn, $sample_group_id, $cache_id);
echo $sample_group_reporter->dataset_report($conn, $sample_group_id, $cache_id);
echo $sample_group_reporter->species_report($conn, $sample_group_id, $cache_id);
echo $sample_group_reporter->measured_values_report($conn, $sample_group_id, $cache_id)
?>

</BODY>

</HTML>