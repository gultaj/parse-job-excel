<?php
require "ParseExcelJob.php";

$fileName = 'data/Vacancies.xls';

$reader = new ParseExcelJob($fileName);
$data = $reader->readData()->parse()->getResultData();
$edu = [];
foreach ($data as $key => $value) {
	$edu[$value['edu']] .= isset($edu[$value['edu']])? ', '.$key : $key;
}
echo '<pre>'.print_r($edu, true).'</pre>';
echo '<pre>'.print_r($data, true).'</pre>';