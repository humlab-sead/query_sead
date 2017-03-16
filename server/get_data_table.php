<?php
/**
 * PHPExcel
 *
 * Copyright (C) 2006 - 2012 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category   PHPExcel
 * @package    PHPExcel
 * @copyright  Copyright (c) 2006 - 2012 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version    1.7.7, 2012-05-19
 */

date_default_timezone_set('Europe/Stockholm');

require_once 'lib/PHPExcel/PHPExcel.php';
require('fb_server_funct.php');

if (!($conn = pg_connect(CONNECTION_STRING))) { echo "Error: pg_connect failed.\n"; exit; }


// The xml-data is containing facet information is processed and all parameters are put into an array for futher use.
$facet_xml_file_location="cache/".$_REQUEST['cache_id']."_facet_xml.xml";
$facet_xml=file_get_contents($facet_xml_file_location);

$facet_params = fb_process_params($facet_xml); 
$facet_params=remove_invalid_selections($conn,$facet_params);

$result_xml_file_location="cache/".$_REQUEST['cache_id']."_result_xml.xml";
$result_xml=file_get_contents($result_xml_file_location);

$result_params = process_result_params($result_xml);

$aggregation_code=$result_params["aggregation_code"];
$q= get_result_data_query($facet_params, $result_params);

$objPHPExcel = new PHPExcel();
$objPHPExcel->getProperties()->setCreator("$applicationTitle , QVIZ browser, HUMlab Umeå Universitet ")
							 ->setLastModifiedBy("HUMlab Umeå Universitet")
							 ->setTitle($applicationTitle)
							 ->setSubject($applicationTitle)
							 ->setDescription($applicationTitle)
							 ->setKeywords("office 2007 openxml php")
							 ->setCategory("Digital språkatlas");

$objWorksheet2 = $objPHPExcel->createSheet();
$objWorksheet2->setTitle('SQL'); // sheet with the SQL for debugging

$objWorksheet1 = $objPHPExcel->createSheet();
$objWorksheet1->setTitle('Filter'); // sheet with the active filters being used in the query

$objWorksheet2->getColumnDimension('B')->setWidth(90);
$objWorksheet2->getStyle('B')->getAlignment()->setWrapText(true); /// set wrap text to the sql column

$objWorksheet2->setCellValueByColumnAndRow(0, 1, 'SQL:'); // add a heading
$objWorksheet2->setCellValueByColumnAndRow(1, 1, $q); // add the SQL

$selection_matrix=derive_selections_to_matrix($facet_params); // get the selection as matrix to be able to populate the filter sheet.
$objWorksheet1->setCellValueByColumnAndRow(0, 2, 'FILTERS');

$columns_base=array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","X","Y","Z");

foreach ($columns_base as $key=>$char)
{
	$columns[]=$char;
}

foreach ($columns_base as $key=>$char1)
{
	foreach ($columns_base as $key=>$char2)
	{
		$columns[]=$char1.''.$char2;
	}
}

$column_counter=0;
// get the selection and add them to the cells
if (isset($selection_matrix))
{
	foreach ($selection_matrix as $facet_code =>$selection_info)
	{
		$objWorksheet1->setCellValueByColumnAndRow($column_counter, 4, $selection_info["display_title"]);
		$row_counter=0;
        if (isset($selection_info["selections"]))
        {
            foreach ($selection_info["selections"] as $sel_key=>$selection_items)
            {               
                if (isset($selection_items["selection_text"]))
                {
                    $objWorksheet1->setCellValueByColumnAndRow($column_counter, 5+$row_counter, $selection_items["selection_text"]);
                    $row_counter++;
                }
            }
        }
		$column_counter++;
	}
}

if (($rs = pg_query($conn, $q)) <= 0) { echo "Error: cannot execute query3. $q \n"; exit; }

$item_counter=1;
$use_count_item=false; //this is use to flag if aggregation is used and then indicate the usage.

$column_counter=0;
foreach($result_params["items"] as $headline)
{
    if ($item_counter==1 && $headline!="parish_level")
    {
        $use_count_item=true;
    }
    $item_counter++;

    // First create header for the column.
    foreach ($result_definition[$headline]["result_item"] as $res_def_key =>$definition_item)
    {
        foreach ($definition_item as $item_type =>$item)
        {
            if ($res_def_key!='sort_item'  && !($res_def_key=='count_item' && !$use_count_item )  )
            {
                // add (counting phras for counting variables)
                if ($res_def_key=='count_item' && $use_count_item) {
                    $extra_text_info=" ".t("(antal med värde)",$facet_params["client_language"])." ";
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow($column_counter, 1, t($item["text"],$facet_params["client_language"]).t($extra_text_info,$facet_params["client_language"]));
                } else {	
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow($column_counter,1, t($item["text"],$facet_params["client_language"]));
                    if (isset($item["word_wrap"]))
                    {
                        $use_word_wrap[$columns[$column_counter]]=true;; // use the result_definition_item to know whether a columns should have word_wrap in excel document
                    }
                }
                $column_counter++;
            }
        }
    }
}
$i=0;
while ($i<$column_counter) // only use the columns that have data
{
    if (isset($columns[$i]))
        $columns_to_use[]=$columns[$i]; // Only use existing elements in the array
    $i++;
}
$columns= $columns_to_use;
$objPHPExcel->setActiveSheetIndex(0);
foreach ($columns as $key =>$column_char)
{
    if (!isset($use_word_wrap[$column_char]))
        $objPHPExcel->getActiveSheet()->getColumnDimension($column_char)->setAutoSize(true); // set autosize to all column
}
if (isset($use_word_wrap))
{
	foreach ($use_word_wrap as $key =>$element)
	{
		$objPHPExcel->getActiveSheet()->getColumnDimension($key)->setWidth(20);
		$objPHPExcel->getActiveSheet()->getStyle($key)->getAlignment()->setWrapText(true);
	}
}

$objPHPExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_LANDSCAPE);

$styleArray = array(
	'font' => array(
		'bold' => true,
	),
	'alignment' => array(
		'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_LEFT,
	),
	'borders' => array(
		'bottom' => array(
			'style' => PHPExcel_Style_Border::BORDER_THIN,
		),
	),
	'fill' => array(
		'type' => PHPExcel_Style_Fill::FILL_GRADIENT_LINEAR,
		'rotation' => 90,
		'startcolor' => array(
			'argb' => 'FFA0A0A0',
		),
		'endcolor' => array(
			'argb' => 'FFFFFFFF',
		),
	),
);
foreach ($columns as $key=>$element)
{
	$last_column=$element;// store the last element lastly by looping the columns
}

$objPHPExcel->getActiveSheet()->getStyle('A1:'.$last_column.'1')->applyFromArray($styleArray);
$objPHPExcel->getActiveSheet()->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(1, 1);


$used_number_of_columns=$column_counter;
$column_counter=0;
$row_counter=2;
while (($row = pg_fetch_assoc($rs) )) 
	{
		$column_counter=0;
		foreach($row as $row_item)
		{
			if ($row_item[0]=="=")
			{	
				$row_item="'".$row_item;
			}
			$objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow($column_counter,$row_counter, $row_item); // store the data in the spreadshet
			$column_counter++;
		}
		$row_counter++;
	}

$objPHPExcel->getActiveSheet(0)->setTitle('Data');
$objPHPExcel->setActiveSheetIndex(0);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="result_'.$application_name.'_'.rand(1,100000).'.xlsx"');
header('Cache-Control: max-age=0');

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;
?>