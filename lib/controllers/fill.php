<?php

class FillController extends Controller
{
	public function tableAction()
	{
		$requiredParams = ['u', 'p', 'h', 'n', 't', 'r'];
		
		if (!$this->_checkParams($requiredParams)) {
			throw new Exception('Missing required params');
		}
		
		$params = array_map(
			function ($value) {
				return is_array($value) ? array_pop($value) : $value; 
			},
			$this->_params
		);
		
		$params['r'] = intval($params['r']);
		if (!$params['r']) {
			throw new Exception('Invalid rows count');
		}
		
		$db = new PDOEx(
			'mysql:dbname=' . $params['n'] . ';host=' . $params['h'],
			$params['u'],
			$params['p']
		);
		
		$table = new TableModel;
		$table->load($db, $params['t']);
		
		$rows = $table->generateRandomRows($params['r']);
		
		$result = 'Generated ' . count($rows) . " rows.\n";
		
		if (isset($params['s'])) {
			$result .= $this->_formatTable($rows) . "\n";
		}
		
		$db->insertData($rows, $params['t']);
		
		return $result;
	}
}
