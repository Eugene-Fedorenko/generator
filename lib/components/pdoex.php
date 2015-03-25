<?php

class PDOEx extends PDO
{
	protected $_dbName;
	protected $_cache;
	
	public function __construct($dsn, $username = null, $password = null, $options = null)
	{
		$matches = [];
		if (preg_match('/dbname=(\w+)/i', $dsn, $matches)) {
			$this->_dbName = $matches[1];
		} else {
			throw new PDOException('Can\'t parse dsn');
		}
		
		$this->_cache = [];
		
		parent::__construct($dsn, $username, $password, $options);
		
		$this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	protected function _checkTable($table)
	{
		return in_array($table, $this->getTables());
	}
	
	public function getTables()
	{
		if (array_key_exists('tables', $this->_cache)) {
			return $this->_cache['tables'];
		}
		
		$sth = $this->query('show tables');
		
		if (!$sth) {
			throw new PDOException('Can\'t retrieve tables list');
		}
		
		$this->_cache['tables'] = $sth->fetchAll(PDO::FETCH_COLUMN, 0);
		
		return $this->_cache['tables'];
	}
	
	public function getFields($table)
	{
		if (!$this->_checkTable($table)) {
			throw new PDOException('Unknown table');
		}
		$sth = $this->query('desc ' . $table);
		
		if (!$sth) {
			throw new PDOException('Can\'t retrieve fields list');
		}
		
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function getRowsCount($table)
	{
		if (!$this->_checkTable($table)) {
			throw new PDOException('Unknown table');
		}
		
		$sth = $this->query('select count(*) from ' . $table);
		
		if (!$sth) {
			throw new PDOException('Can\'t retrieve rows count');
		}
		
		return $sth->fetchColumn();
	}
	
	public function getTableInfo($table)
	{
		if (!$this->_checkTable($table)) {
			throw new PDOException('Unknown table');
		}
		
		$sth = $this->prepare('select * from information_schema.TABLES where TABLE_SCHEMA = ? and TABLE_NAME = ?');
		
		if (!$sth->execute(array($this->_dbName, $table))) {
			throw new PDOException('Can\'t retrieve table info');
		}
		
		return $sth->fetch(PDO::FETCH_ASSOC);
	}
	
	public function getSimpleColumns($table)
	{
		if (!$this->_checkTable($table)) {
			throw new PDOException('Unknown table');
		}
		
		$sth = $this->prepare(
			"select c.COLUMN_NAME,c.DATA_TYPE,c.CHARACTER_MAXIMUM_LENGTH,c.NUMERIC_PRECISION,"
			. "c.NUMERIC_SCALE,c.COLUMN_TYPE like '%unsigned%' as `UNSIGNED` "
			. "from information_schema.COLUMNS c "
			. "left join information_schema.KEY_COLUMN_USAGE kcu "
			. "on(kcu.TABLE_SCHEMA = c.TABLE_SCHEMA and kcu.TABLE_NAME = c.TABLE_NAME and kcu.COLUMN_NAME = c.COLUMN_NAME) "
			. "where c.TABLE_SCHEMA = ? and c.TABLE_NAME = ? and c.EXTRA not like '%auto_increment%' "
			. "and kcu.COLUMN_NAME is null "
		);
		
		if (!$sth->execute(array($this->_dbName, $table))) {
			throw new PDOException('Can\'t retrieve simple columns');
		}
		
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
	
	public function getColumnsByForeignKeys($table)
	{
		if (!$this->_checkTable($table)) {
			throw new PDOException('Unknown table');
		}
		
		$sth = $this->prepare(
			"select kcu.CONSTRAINT_NAME, kcu.COLUMN_NAME, kcu.REFERENCED_TABLE_SCHEMA, "
			. "kcu.REFERENCED_TABLE_NAME, kcu.REFERENCED_COLUMN_NAME "
			. "from information_schema.COLUMNS c "
			. "inner join information_schema.KEY_COLUMN_USAGE kcu "
			. "on(kcu.TABLE_SCHEMA = c.TABLE_SCHEMA and kcu.TABLE_NAME = c.TABLE_NAME "
			. "and kcu.COLUMN_NAME = c.COLUMN_NAME) "
			. "inner join information_schema.TABLE_CONSTRAINTS tc "
			. "on(tc.TABLE_SCHEMA = kcu.TABLE_SCHEMA and tc.TABLE_NAME = kcu.TABLE_NAME and "
			. "tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME and tc.CONSTRAINT_TYPE = 'FOREIGN KEY') "
			. "where kcu.TABLE_SCHEMA = ? and kcu.TABLE_NAME = ?"
		);
		
		if (!$sth->execute(array($this->_dbName, $table))) {
			throw new PDOException('Can\'t retrieve foreign keys');
		}
		
		$res = [];
		
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$res[$row['CONSTRAINT_NAME']][$row['COLUMN_NAME']] = $row;
		}
		
		return $res;
	}
	
	public function getColumnsByUniqueKeys($table)
	{
		if (!$this->_checkTable($table)) {
			throw new PDOException('Unknown table');
		}
		
		$sth = $this->prepare(
			"select kcu.CONSTRAINT_NAME,c.COLUMN_NAME,c.DATA_TYPE,"
			. "c.CHARACTER_MAXIMUM_LENGTH,c.NUMERIC_PRECISION,c.NUMERIC_SCALE,c.COLUMN_TYPE like '%unsigned%' as `UNSIGNED` "
			. "from information_schema.COLUMNS c "
			. "inner join information_schema.KEY_COLUMN_USAGE kcu "
			. "on(kcu.TABLE_SCHEMA = c.TABLE_SCHEMA and kcu.TABLE_NAME = c.TABLE_NAME "
			. "and kcu.COLUMN_NAME = c.COLUMN_NAME) "
			. "inner join information_schema.TABLE_CONSTRAINTS tc "
			. "on(tc.TABLE_SCHEMA = kcu.TABLE_SCHEMA and tc.TABLE_NAME = kcu.TABLE_NAME "
			. "and tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME and tc.CONSTRAINT_TYPE in('PRIMARY KEY', 'UNIQUE')) "
			. "where kcu.TABLE_SCHEMA = ? and kcu.TABLE_NAME = ?"
		);
		
		if (!$sth->execute(array($this->_dbName, $table))) {
			throw new PDOException('Can\'t retrieve unique keys');
		}
		
		$res = [];
		
		while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
			$res[$row['CONSTRAINT_NAME']][$row['COLUMN_NAME']] = $row;
		}
		
		return $res;
	}
	
	public function getAutoIncrementColumns($table)
	{
		if (!$this->_checkTable($table)) {
			throw new PDOException('Unknown table');
		}
		
		$sth = $this->prepare(
			"select COLUMN_NAME from information_schema.COLUMNS "
			. "where TABLE_SCHEMA = ? and TABLE_NAME = ? and EXTRA like '%auto_increment%'"
		);
		
		if (!$sth->execute(array($this->_dbName, $table))) {
			throw new PDOException('Can\'t retrieve unique keys');
		}
		
		return $sth->fetchAll(PDO::FETCH_COLUMN, 0);
	}
	
	public function getColumnData($column, $table, $schema = NULL)
	{
		if (empty($schema)) {
			$schema = $this->_dbName;
		}
		
		$sth = $this->query(
			'select `' . $column . '` from `' . $schema . '`.`' . $table . '`'
		);
		
		if (!$sth) {
			throw new PDOException('Can\'t retrieve column data');
		}
		
		return $sth->fetchAll(PDO::FETCH_COLUMN, 0);
	}
	
	public function insertData($data, $table)
	{
		if (empty($data)) {
			return false;
		}
		if (!$this->_checkTable($table)) {
			throw new PDOException('Unknown table');
		}
		
		$columns = array_keys(reset($data));
		
		$query = 'insert into `' . $table . '` (`' . implode('`,`', $columns) . '`) '
			. 'values (:' . implode(',:', $columns) . ')';
		
		$sth = $this->prepare($query);
		
		foreach ($data as $row) {
			$params = [];
			foreach ($columns as $colName) {
				$params[':' . $colName] = $row[$colName];
			}
			
			if (!$sth->execute($params)) {
				throw new PDOException('Can\'t insert data');
			}
		}
	}
	
	public function getData($table)
	{
		if (!$this->_checkTable($table)) {
			throw new PDOException('Unknown table');
		}
		
		$sth = $this->query('select * from `' . $table . '`');
		
		if (!$sth) {
			throw new PDOException('Can\'t retrieve data');
		}
		
		return $sth->fetchAll(PDO::FETCH_ASSOC);
	}
}
