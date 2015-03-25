<?php

class Controller
{
	protected $_params;
	
	public function __construct($params)
	{
		$this->_params = $params;
	}
	
	protected function _checkParams($requiredParams)
	{
		$diff = array_diff($requiredParams, array_keys($this->_params));

		return empty($diff);
	}
	
	protected function _formatTable($table)
	{
		if (empty($table)) {
			return '';
		}
		
		$columnMaxLength = [];
		$columnCount = count(reset($table));
		$columnKeys = array_keys(reset($table));
		$result = '';
		
		array_unshift($table, array_combine($columnKeys, $columnKeys));
		
		foreach ($columnKeys as $colKey) {
			$column = array_column($table, $colKey);
			$columnMaxLength[$colKey] = max(array_map('strlen', $column));
		}
		
		$rowNum = 0;
		$br = "|"
			. str_repeat('-', array_sum($columnMaxLength) + count($columnMaxLength) * 3 - 1)
			. "|";
		
		$rows = array_map(
			function ($row) use ($columnMaxLength, &$rowNum, $br) {
				$str = '| ';
				array_walk(
					$row,
					function ($v, $k) use ($columnMaxLength, &$str) {
						$str .= $v . str_repeat(' ', $columnMaxLength[$k] - strlen($v)) . ' | ';
					}
				);
				
				if ($rowNum == 0) {
					$str .= "\n" . $br;
				}
				
				++$rowNum;
				return $str;
			},
			$table
		);
		
		return $br . "\n" . implode("\n", $rows) . "\n" . $br;
	}
}