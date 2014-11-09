<?php 

// setlocale(LC_ALL, 'ru_RU.CP1251');
require "vendor/autoload.php";

$fileName = 'data/Vacancies.xls';

$reader = PHPExcel_IOFactory::createReader( PHPExcel_IOFactory::identify($fileName) );
$phpExcel = $reader->load($fileName);

$vacancies = [];

foreach ($phpExcel->getWorksheetIterator() as $itemSheet => $sheet) {
	foreach ($sheet->getRowIterator() as $row) {
		if ($row->getRowIndex() < 4) continue;
		$cellIterator = $row->getCellIterator();
		$cellIterator->setIterateOnlyExistingCells(true);
		$vacancy = [];
		foreach ($cellIterator as $cell) {
			$vacancy[] = $cell->getCalculatedValue();
		}
		$vacancies[] = $vacancy;
	}
}
unset($phpExcel);

$email = "/\s?([a-zA-Z0-9_\-\.]+)@?((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)/iu";
$phone = "/([\d-]{6,11})(,?\s?\(?\w+\)?)?/iu";


foreach ($vacancies as $key => &$vacancy) {
	$data = preg_replace("/(гродненская\s*область\s*)/ui", '', $vacancy[1]);
	$vacancy[1] = $data;
	$vacancy[3] = mb_strtolower($vacancy[3], 'utf-8');
	$vacancy[4] = mb_strtolower($vacancy[4], 'utf-8');
	$vacancy[5] = mb_strtolower($vacancy[5], 'utf-8');
	if (preg_match_all($email, $data, $matches)) {
		$data = preg_replace($email, '', $data);
		$e = $s = '';
		foreach ($matches[0] as $value) {
			if (preg_match("/@/", $value)) {
				$e .=  (empty($e) ? '' : ', ') . $value ;
			} else {
				$s .=  (empty($s) ? '' : ', ') . $value ;	
			}
		}
		$vacancies[$key]['site'] = strtolower(trim($s));
		$vacancies[$key]['email'] = strtolower(trim($e));
	}
	if (preg_match_all($phone, $data, $matches)) {
		$data = preg_replace($phone, '', $data);
		$t = '';
		foreach ($matches[0] as $value) {
			$t .=  (empty($t) ? '' : ', ') . $value ;
		}
		$vacancies[$key]['phone'] = trim($t);
	}


	if (preg_match("/(г\.?\s*лида)/ui", $data)) {
		$street = preg_replace("/(г\.?\s*лида)/ui", '', $data);
		$street = trim($street);
		// $street = preg_replace_callback("/(ул|пер|проспект)?\.?\s*(\d)*([^0-9]*)/ui", function($matches) {
		// 	return mb_strtolower($matches[1], 'utf-8').". ".mb_convert_case($matches[3], MB_CASE_TITLE, 'utf-8')." ".$matches[2];
		// }, $street);
		// $email = "[\s\n]?([\w\.\-]*@[\w+\.\-]*)\s?";
		// if (preg_match("/\s+([\w\.\-]*@[\w]+[\.]+[\-]?[\.\w]{2,})\s?/i", $address, $matches)) {
		$vacancies[$key][1] = "г. Лида, " . $street;
		// echo $vacancies[$key][1].'<br>';

	}
		

}

echo '<pre>'.print_r($vacancies, true).'</pre>';