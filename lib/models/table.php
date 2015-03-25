<?php

class TableModel extends Model
{
	protected $_name;
	protected $_engine;
	protected $_autoIncrement;
	protected $_data;
	protected $_dbConnection;
	protected $_originalDataCount = 0;
	
	protected $_simpleColumns;
	protected $_autoIncrementColumns;
	
	protected $_uniqueColumns;
	protected $_uniqueConstraints;
	protected $_uniqueConstraintsByColumns;
	
	protected $_foreignColumns;
	protected $_foreignConstraints;
	protected $_foreignConstraintsByColumns;
	
	public function load(PDOEx $db, $table)
	{
		$this->_name = $table;
		
		$info = $db->getTableInfo($table);
		
		$this->_engine = $info['ENGINE'];
		$this->_autoIncrement = $info['AUTO_INCREMENT'];
		$this->_dbConnection = $db;
		
		$this->_simpleColumns = $db->getSimpleColumns($table);
		
		$this->_autoIncrementColumns = $db->getAutoIncrementColumns($table);
		$this->_autoIncrementColumns = array_combine($this->_autoIncrementColumns, $this->_autoIncrementColumns);
		
		$this->_uniqueConstraints = $db->getColumnsByUniqueKeys($table);
		$this->_uniqueConstraintsByColumns = [];
		$this->_uniqueColumns = [];
		foreach ($this->_uniqueConstraints as $constraint) {
			foreach ($constraint as $column) {
				$this->_uniqueConstraintsByColumns[$column['COLUMN_NAME']][] = $column['CONSTRAINT_NAME'];
				$this->_uniqueColumns[$column['COLUMN_NAME']] = $column;
			}
		}
		
		if ($this->_engine == 'InnoDB') {
			$this->_foreignConstraints = $db->getColumnsByForeignKeys($table);
		} else {
			$this->_foreignConstraints = [];
		}
		$this->_foreignConstraintsByColumns = [];
		$this->_foreignColumns = [];
		foreach ($this->_foreignConstraints as $constraint) {
			foreach ($constraint as $column) {
				$this->_foreignConstraintsByColumns[$column['COLUMN_NAME']][] = $column['CONSTRAINT_NAME'];
				$this->_foreignColumns[$column['COLUMN_NAME']] = $column;
			}
		}
		
		$this->_data = [];
		
		if (!empty($this->_uniqueColumns)) {
			$this->_loadData();
		}
	}
	
	protected function _loadData()
	{
		$data = $this->_dbConnection->getData($this->_name);
		$keys = [];
		
		if (!empty($data)) {
			$keys = array_keys(reset($data));
			$this->_originalDataCount = count($data);
		}
		
		foreach ($keys as $key) {
			$this->_data[$key] = array_column($data, $key);
		}
	}
	
	protected function _generateDataForSimpleColumns($count)
	{
		foreach ($this->_simpleColumns as $column) {
			$callback = $this->_getRandomizerCallback($column);
			$func = $callback[0];
			$args = $callback[1];
			
			for ($i = 0; $i < $count; $i++) {
				$this->_data[$column['COLUMN_NAME']][] = call_user_func_array(
					array('Randomizer', $func),
					$args
				);
			}
		}
	}
	
	protected function _getRandomizerCallback($column)
	{
		$func = '';
		$args = [];
		
		if ($column['NUMERIC_PRECISION']) {
			$func = 'getMysqlNumeric';
			$args = [
				$column['NUMERIC_PRECISION'],
				$column['NUMERIC_SCALE'],
				$column['UNSIGNED']
			];
		} else if ($column['CHARACTER_MAXIMUM_LENGTH']) {
			$func = 'getMysqlString';
			$args = [
				$column['CHARACTER_MAXIMUM_LENGTH']
			];
		} else if ($column['DATA_TYPE'] == 'year') {
			$func = 'getYear';
		} else if ($column['DATA_TYPE'] == 'date') {
			$func = 'getMysqlDate';
		} else if ($column['DATA_TYPE'] == 'time') {
			$func = 'getMysqlTime';
		} else if (in_array($column['DATA_TYPE'], ['timestamp', 'datetime'])) {
			$func = 'getMysqlDateTime';
		}
		
		return [$func, $args];
	}
	
	protected function _checkUniqueConstraint($constraintName, $columnName, $value)
	{
		if (empty($this->_data[$columnName])) {
			return true;
		}
		
		$constraintColumnNames = array_keys($this->_uniqueConstraints[$constraintName]);
		
		$data = &$this->_data;
		$nextValueIndex = count($this->_data[$columnName]);
		
		$columnsWithoutEnoughValues = array_filter(
			array_diff($constraintColumnNames, [$columnName]),
			function ($v) use ($nextValueIndex, $data) {
				return !isset($data[$v]) || !array_key_exists($nextValueIndex, $data[$v]);
			}
		);
		
		if (!empty($columnsWithoutEnoughValues)) {
			return true;
		}
		
		$dataToCheck = [$columnName => strtolower($value)];
		
		foreach ($constraintColumnNames as $colName) {
			if ($colName != $columnName) {
				$dataToCheck[$colName] = $this->_data[$colName][$nextValueIndex];
			}
		}
		
		for ($i = 0; $i < $nextValueIndex; $i++) {
			$row = [];
			
			foreach ($constraintColumnNames as $colName) {
				$row[$colName] = $this->_data[$colName][$i];
			}
			
			$diff = array_udiff_assoc(
				$dataToCheck,
				$row,
				'strcasecmp'
			);
			
			if (empty($diff)) {
				return false;
			}
		}
		
		return true;
	}
	
	protected function _checkUniqueConstraints($columnName, $value)
	{
		foreach ($this->_uniqueConstraintsByColumns[$columnName] as $constraintName) {
			if (!$this->_checkUniqueConstraint($constraintName, $columnName, $value)) {
				return false;
			}
		}
		
		return true;
	}
	
	protected function _generateDataForUniqueColumns($count)
	{
		$retriesLimit = 100;
		
		foreach ($this->_uniqueColumns as $column) {
			if (isset($this->_foreignConstraintsByColumns[$column['COLUMN_NAME']])) {
				continue;
			}
			
			$callback = $this->_getRandomizerCallback($column);
			$func = $callback[0];
			$args = $callback[1];
			
			for ($i = 0; $i < $count; $i++) {
				$value = '';
				$x = 0;
				
				do {
					if ($x > $retriesLimit) {
						throw new Exception('Unique value generation retries limit exceeded');
					}
					
					if (isset($this->_autoIncrementColumns[$column['COLUMN_NAME']])) {
						$value = $this->_getAutoIncrementFieldValue($column['COLUMN_NAME']);
					} else {
						$value = call_user_func_array(
							array('Randomizer', $func),
							$args
						);
					}
					
					++$x;
				} while(!$this->_checkUniqueConstraints($column['COLUMN_NAME'], $value));
				
				$this->_data[$column['COLUMN_NAME']][] = $value;
			}
		}
	}
	
	protected function _getAutoIncrementFieldValue($columnName)
	{
		if (empty($this->_data[$columnName])) {
			return $this->_autoIncrement;
		}
		return end($this->_data[$columnName]) + 1;
	}
	
	protected function _checkForeignConstraintIntersection($constraintName, $columnName)
	{
		$foreignColumns = array_keys($this->_foreignConstraints[$constraintName]);
		
		foreach ($this->_uniqueConstraints as $uniqueConstraint) {
			$uniqueColumns = array_keys($uniqueConstraint);
			if (
				array_intersect($uniqueColumns, $foreignColumns) == $uniqueColumns
				&& in_array($columnName, $uniqueColumns)
			) {
				return true;
			}
		}
		
		return false;
	}
	
	public function _getIntersectedConstraints()
	{
		$res = [];
		foreach ($this->_foreignConstraints as $foreignConstraintName => $foreignColumns) {
			foreach ($this->_uniqueConstraints as $uniqueConstraintName => $uniqueColumns) {
				$uniqueColumnNames = array_keys($uniqueColumns);
				$foreignColumnNames = array_keys($foreignColumns);
				
				if (array_intersect($uniqueColumnNames, $foreignColumnNames) == $uniqueColumnNames) {
					$res[$foreignConstraintName][] = $uniqueConstraintName;
				}
			}
		}
		return $res;
	}
	
	protected function _deleteRow(&$data, $key, $columnNames)
	{
		foreach ($columnNames as $columnName) {
			unset($data[$columnName][$key]);
		}
	}
	
	protected function _filterDataByConstraint(&$data, $foreignConstraintName, $uniqueConstraintName)
	{
		$foreignColumnNames = array_keys($this->_foreignConstraints[$foreignConstraintName]);
		$uniqueColumnNames = array_keys($this->_uniqueConstraints[$uniqueConstraintName]);
		
		$keys = [];
		foreach ($uniqueColumnNames as $columnName) {
			$keys = array_merge($keys, array_keys($data[$columnName]));
		}
		$keys = array_unique($keys);
		
		foreach ($keys as $key) {
			$dataToCheck1 = [];
			foreach ($uniqueColumnNames as $columnName) {
				if (!isset($data[$columnName][$key])) {
					$this->_deleteRow($data, $key, $foreignColumnNames);
					continue 2;
				}
				$dataToCheck1[$columnName] = $data[$columnName][$key];
			}
			
			foreach ($keys as $key2) {
				if ($key2 == $key) {
					continue;
				}
				$dataToCheck2 = [];
				foreach ($uniqueColumnNames as $columnName) {
					if (!isset($data[$columnName][$key2])) {
						$this->_deleteRow($data, $key2, $foreignColumnNames);
						continue 2;
					}
					$dataToCheck2[$columnName] = $data[$columnName][$key2];
				}
				
				if ($dataToCheck1 == $dataToCheck2) {
					$this->_deleteRow($data, $key2, $foreignColumnNames);
				}
			}
			
			for ($i = 0; $i < $this->_originalDataCount; $i++) {
				$dataToCheck2 = [];
				foreach ($uniqueColumnNames as $columnName) {
					$dataToCheck2[$columnName] = $this->_data[$columnName][$i];
				}
				
				if ($dataToCheck1 == $dataToCheck2) {
					$this->_deleteRow($data, $key, $foreignColumnNames);
				}
			}
		}
	}
	
	protected function _generateDataForForeignColumns($count)
	{
		$data = [];
		$intersection = $this->_getIntersectedConstraints();
		
		foreach ($this->_foreignColumns as $column) {
			$data[$column['COLUMN_NAME']] =
				$this->_dbConnection->getColumnData(
					$column['REFERENCED_COLUMN_NAME'],
					$column['REFERENCED_TABLE_NAME'],
					$column['REFERENCED_TABLE_SCHEMA']
				);
		}
		
		foreach ($intersection as $foreignConstraintName => $uniqueConstraintNames) {
			foreach ($uniqueConstraintNames as $uniqueConstraintName) {
				$this->_filterDataByConstraint($data, $foreignConstraintName, $uniqueConstraintName);
			}
		}
		
		foreach ($this->_foreignColumns as $column) {
			if (!isset($this->_data[$column['COLUMN_NAME']])) {
				$this->_data[$column['COLUMN_NAME']] = [];
			}
			
			if (count($data[$column['COLUMN_NAME']]) < $count) {
				if (
					$this->_checkForeignConstraintIntersection($column['CONSTRAINT_NAME'], $column['COLUMN_NAME'])
				) {
					throw new Exception('Not enough data in referenced table');
				}
				
				$times = intval($count / count($data[$column['COLUMN_NAME']]));
				$leftRows = $count % count($data[$column['COLUMN_NAME']]);
				
				for ($i = 0; $i < $times; $i++) {
					$this->_data[$column['COLUMN_NAME']] = array_merge(
						$this->_data[$column['COLUMN_NAME']],
						$data[$column['COLUMN_NAME']]
					);
				}
				
				if ($leftRows) {
					$this->_data[$column['COLUMN_NAME']] = array_merge(
						$this->_data[$column['COLUMN_NAME']],
						array_slice(
							$data[$column['COLUMN_NAME']],
							0,
							$leftRows
						)
					);
				}
				
			} else {
				$this->_data[$column['COLUMN_NAME']] = array_merge(
					$this->_data[$column['COLUMN_NAME']],
					array_slice(
						$data[$column['COLUMN_NAME']],
						0,
						$count
					)
				);
			}
		}
	}
	
	protected function _getTableData()
	{
		$count = count(reset($this->_data));
		$res = [];
		$columns = array_keys($this->_data);
		
		for ($i = $this->_originalDataCount; $i < $count; $i++) {
			foreach ($columns as $colName) {
				$res[$i][$colName] = $this->_data[$colName][$i];
			}
		}
		
		return $res;
	}
	
	public function generateRandomRows($count = 1)
	{
		$this->_generateDataForSimpleColumns($count);
		$this->_generateDataForForeignColumns($count);
		$this->_generateDataForUniqueColumns($count);
		
		return $this->_getTableData();
	}
	
}
