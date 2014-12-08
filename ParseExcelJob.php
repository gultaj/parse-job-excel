<?php 
require "vendor/phpoffice/phpexcel/Classes/PHPExcel.php";

class ParseExcelJob {

	private $reader;
	private $data = [];
	private $parse = [];

	public function __construct($fileName) {
		$reader = PHPExcel_IOFactory::createReader( PHPExcel_IOFactory::identify($fileName) );
		$this->reader = $reader->load($fileName);
		return $this;
	}

	public function readData() {
		foreach ($this->reader->getWorksheetIterator() as $itemSheet => $sheet) {
			foreach ($sheet->getRowIterator() as $row) {
				if ($row->getRowIndex() < 4) continue;
				$cellIterator = $row->getCellIterator();
				$cellIterator->setIterateOnlyExistingCells(true);
				$vacancy = [];
				foreach ($cellIterator as $cell) {
					$vacancy[] = $cell->getCalculatedValue();
				}
				$this->data[] = $vacancy;
			}
		}
		$this->reader = null;
		return $this;
	}

	public function getResultData() {
		return $this->parse;
	}

	public function parse() {
		foreach ($this->data as $key => $vacancy) {
			$data = preg_replace("/(гродненская\s*область\s*)?/ui", '', $vacancy[1]);
			$this->parseCommon($vacancy, $key);
			$this->parseEmail($data, $key);
			$this->parsePhone($data, $key);
			if (!$this->parseLocation($data, $key)) continue;
			$data = $this->sanitizeData($data);
			$this->parseAddress($data, $key);
		}
		return $this;
	}

	private function parseCommon($data, $key) {
		$this->parse[$key]['company'] = trim($data[0]);
		$this->parse[$key]['vacancy'] = trim($data[2]);
		$this->parse[$key]['salary'] = trim($data[6]);
		$this->parse[$key]['date'] = trim($data[7]);
		$this->parse[$key]['edu'] = mb_strtolower($data[3], 'utf-8');
		$this->parse[$key]['shift'] = mb_strtolower($data[4], 'utf-8');
		$this->parse[$key]['time'] = mb_strtolower($data[5], 'utf-8');
	}

	private function parseEmail($data, $key) {
		$email = "/\s+(([a-z0-9_\-\.]+)([@.])+([\w\-\.]+)?)/i";
		if (preg_match_all($email, $data, $matches)) {
			$data = preg_replace($email, '', $data);
			$site = $email = '';
			foreach ($matches[0] as $value) {
				if (preg_match("/@/", $value)) {
					$email .=  (empty($email) ? '' : ', ') . strtolower(trim($value));
				} else {
					$site .=  (empty($site) ? '' : ', ') . strtolower(trim($value));	
				}
			}
			$this->parse[$key]['email'] = $email;
			$this->parse[$key]['site'] = $site;
		}
	}

	private function parsePhone($data, $key) {
		if (preg_match("/([\d\-]{6,11})+/iu", $data, $matches, PREG_OFFSET_CAPTURE)) {
			$phone = substr($data, $matches[0][1]);
			$data = str_replace($phone, '', $data);
			$this->parse[$key]['phone'] = trim($phone);
		}
	}

	private function parseLocation($data, $key) {
		$loc = "/\s?(г[\.\s]+)(\b\w+)/ui"; // город
		if (preg_match($loc, $data, $matches)) {
			if (preg_match("/гродно/ui", $matches[2])) {
				unset($this->parse[$key]);
				return false;
			}
			$data = preg_replace($loc, '', $data);
			$this->parse[$key]['locate'] = "г. " . mb_convert_case(trim($matches[2]), MB_CASE_TITLE, 'utf-8');
		}
		$loc = "/\s?(д[\.\s]+)(\b\w+)/ui"; // деревня
		if (preg_match($loc, $data, $matches)) {
			$data = preg_replace($loc, '', $data);
			$this->parse[$key]['locate'] = "д. " . mb_convert_case(trim($matches[2]), MB_CASE_TITLE, 'utf-8');
		}
		return true;
	}

	private function parseAddress($data, $key) {
		$data = mb_convert_case($data, MB_CASE_TITLE, 'utf-8');
		preg_match("/(\b(ул|пер|проспект)[\s\.]+)/ui", $data, $i);
		$address = preg_replace_callback("/(\b(ул|пер|проспект)[\s\.]+)/ui", function($matches) {
			if (!preg_match("/проспект/ui", $matches[1])) {
				$matches[1] = trim($matches[2]) . ". ";
			}
			return mb_strtolower($matches[1], 'utf-8');
		}, $data);
		$this->parse[$key]['address'] = $address;
	}

	private function sanitizeData($data) {
		$data = preg_replace("/\s+\w+@/ui", "", $data);
		$data = preg_replace("/[,@]/ui", "", $data);
		$data = preg_replace("/\./ui", ". ", $data);
		$data = preg_replace("/\s+/ui", " ", $data);
		$data = preg_replace("/лидский район/ui", "", $data);
		return $data;
	}

}
 ?>
