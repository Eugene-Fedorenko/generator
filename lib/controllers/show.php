<?php

class ShowController extends Controller
{
	public function tablesAction()
	{
		$requiredParams = ['u', 'p', 'h', 'n'];
		
		if (!$this->_checkParams($requiredParams)) {
			throw new Exception('Missing required params');
		}
		
		$params = array_map(
			function ($value) {
				return is_array($value) ? array_pop($value) : $value; 
			},
			$this->_params
		);
		
		$db = new PDOEx(
			'mysql:dbname=' . $params['n'] . ';host=' . $params['h'],
			$params['u'],
			$params['p']
		);
		
		return implode("\n", $db->getTables()) . "\n";
	}
	
	public function tableAction()
	{
		$requiredParams = ['u', 'p', 'h', 'n', 't'];
		
		if (!$this->_checkParams($requiredParams)) {
			throw new Exception('Missing required params');
		}
		
		$params = array_map(
			function ($value) {
				return is_array($value) ? array_pop($value) : $value; 
			},
			$this->_params
		);
		
		$db = new PDOEx(
			'mysql:dbname=' . $params['n'] . ';host=' . $params['h'],
			$params['u'],
			$params['p']
		);
		
		$fields = $db->getFields($params['t']);
		
		if (empty($fields) || empty($fields[0])) {
			return 'Nothing found';
		}
		
		return $this->_formatTable($fields) . "\n"
			. 'Rows count: ' . $db->getRowsCount($params['t']) . "\n";
	}
}
