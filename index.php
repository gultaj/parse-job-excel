<?php 
	// phpinfo();
// // setlocale(LC_ALL, 'ru_RU');
// if (!isset($_FILES['data'])) {
	
// 	?>
 	<!-- <form method="post" enctype="multipart/form-data">
// 		<input type="file" name="data">
// 		<input type="submit" value="Send">
// 	</form> -->
 	<?php
// 	die();
// }
// $target_dir = "data/";
// $target_file = $target_dir . time() . basename($_FILES["data"]["name"]);
// if (file_exists($target_file)) {
//     echo "Sorry, file already exists.";
// 	die();
// }

// if (move_uploaded_file($_FILES["data"]["tmp_name"], $target_file)) {
//     echo "The file ". basename( $_FILES["data"]["name"]). " has been uploaded.";
// } else {
//     echo "Sorry, there was an error uploading your file.";
// }
// unset($_FILES["data"]);

setlocale(LC_ALL, 'ru_RU.utf-8');
require "vendor/autoload.php";

$fileName = 'data/Vacancies.xls';
// $fileName = $target_file;

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

// $email = "/\s?([a-zA-Z0-9_\-\.]+)@?(([a-zA-Z0-9\-]+\.)+)([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)/iu";
$email = "/\s+(([a-z0-9_\-\.]+)([@.])+([\w\-\.]+)?)/i";

foreach ($vacancies as $key => &$vacancy) {
	$arr = [];
	$data = preg_replace("/(гродненская\s*область\s*)?/ui", '', $vacancy[1]);
	$arr['company'] = trim($vacancy[0]);
	$arr['vacancy'] = trim($vacancy[2]);
	$arr['salary'] = trim($vacancy[6]);
	$arr['date'] = trim($vacancy[7]);
	$arr['edu'] = mb_strtolower($vacancy[3], 'utf-8');
	$arr['shift'] = mb_strtolower($vacancy[4], 'utf-8');
	$arr['time'] = mb_strtolower($vacancy[5], 'utf-8');

	if (preg_match_all($email, $data, $matches)) {
		$data = preg_replace($email, '', $data);
		$arr['site'] = $arr['email'] = '';
		foreach ($matches[0] as $value) {
			if (preg_match("/@/", $value)) {
				$arr['email'] .=  (empty($arr['email']) ? '' : ', ') . strtolower(trim($value));
			} else {
				$arr['site'] .=  (empty($arr['site']) ? '' : ', ') . strtolower(trim($value));	
			}
		}
	}
	if (preg_match("/([\d\-]{6,11})+/iu", $data, $matches, PREG_OFFSET_CAPTURE)) {
		$phone = substr($data, $matches[0][1]);
		$data = str_replace($phone, '', $data);
		$arr['phone'] = trim($phone);
	}

	$loc = "/\s?(г[\.\s]+)(\b\w+)/ui";
	if (preg_match($loc, $data, $matches)) {
		$data = preg_replace($loc, '', $data);
		$arr['locate'] = "г. " . mb_convert_case(trim($matches[2]), MB_CASE_TITLE, 'utf-8');
	}
	$loc = "/\s(д[\.\s]+)(\b\w+)/ui";
	if (preg_match($loc, $data, $matches)) {
		$data = preg_replace($loc, '', $data);
		$arr['locate'] = "д. " . mb_convert_case(trim($matches[2]), MB_CASE_TITLE, 'utf-8');
	}

	$data = preg_replace("/\s+\w+@/ui", "", $data);
	$data = preg_replace("/[,@]/ui", "", $data);
	$data = preg_replace("/\./ui", ". ", $data);
	$data = preg_replace("/\s+/ui", " ", $data);


	$data = mb_convert_case($data, MB_CASE_TITLE, 'utf-8');


	if (preg_match("/(г\.?\s*лида)/ui", $data, $matches)) {
		$street = preg_replace("/(г\.?\s*лида)/ui", '', $data);
		// $city = $matches[2];
		// echo '<pre>'.print_r($matches, true) . '</pre>';
		$street = trim($street);
		
		$street =  preg_replace_callback("/((ул|пер|проспект)[\.\s])+/ui", function($matches) {
			if (!preg_match("/(проспект)/ui", $matches[1])) {
				if (!preg_match("/\./ui", $matches[1])) {
					$matches[1] = trim($matches[1]).'. ';
				}
			}
			return mb_strtolower($matches[1], 'utf-8');//mb_strtolower($matches[1], 'utf-8');
		}, $street);

		$data = "г. Лида, " . $street;

	}

	if (preg_match("/(район)/ui", $data)) {
		$data = preg_replace("/(район)/ui", 'район, ', $data);
		$data = preg_replace("/\s+(д\.?\s+)/ui", ' д. ', $data);
		$data = preg_replace("/\s+(г\s?)/ui", ' г. ', $data);
		$data = preg_replace("/\s+(ул\s?\s?)/ui", ', ул. ', $data);
	}
	$vacancy = $arr;
	// echo $vacancy[1].'<br>';
		

}

echo '<pre>'.print_r($vacancies, true).'</pre>';