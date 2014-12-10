<?php
require "ParseExcelJob.php";

$fileName = 'data/Vacancies.xls';

$reader = new ParseExcelJob($fileName);
$data = $reader->readData()->parse()->getResultData();
$edu = $shift = $time = [];
foreach ($data as $key => $value) {
	$edu[$value['edu']] .= isset($edu[$value['edu']])? ', '.$key : $key;
}
foreach ($data as $key => $value) {
	$shift[$value['shift']] .= isset($shift[$value['shift']])? ', '.$key : $key;
}
foreach ($data as $key => $value) {
	$time[$value['time']] .= isset($time[$value['time']])? ', '.$key : $key;
}
echo '<pre>'.print_r($edu, true).'</pre>';
echo '<pre>'.print_r($shift, true).'</pre>';
echo '<pre>'.print_r($time, true).'</pre>';
echo '<pre>'.print_r($data, true).'</pre>';