<?php

class Randomizer
{
	protected static $_symbols = <<<SYMBOLS
ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789 .,!\n
SYMBOLS;
	
	public static function getMysqlNumeric($precision, $scale, $unsigned)
	{
		$res = '';
		
		$precision = mt_rand(1 + $scale, $precision);
		if ($scale) {
			$scale = mt_rand(0, $scale);
		}
		
		$exclude = $scale + 1 + ($unsigned ? 0 : 1);
		
		$length = $precision;
		if ($length > $exclude) {
			$length -= $exclude;
		}
		
		if (!$length) {
			$length = 1;
		}
		
		if (!$unsigned && mt_rand(0, 1)) {
			$res .= '-';
		}
		
		for ($i = 0; $i < $length; $i++) {
			$res .= mt_rand(!$i ? 1 : 0, 9);
		}
		
		if ($scale) {
			$res .= '.';
			for ($i = 0; $i < $scale; $i++) {
				$res .= mt_rand(0, 9);
			}
		}
		
		return $res;
	}
	
	public static function getMysqlString($maxLength)
	{
		$length = mt_rand(1, $maxLength);
		$res = '';
		$partLength = 1000;
		$multiplier = intval($length / $partLength);
		$symbolsCount = strlen(static::$_symbols);
		
		if ($multiplier > 1) {
			$length = $partLength;
		}
		
		for ($i = 0; $i < $length; $i++) {
			$res .= static::$_symbols[mt_rand(0, $symbolsCount - 1)];
		}
		
		if ($multiplier > 1) {
			$res = str_repeat($res, $multiplier);
		}
		
		return $res;
	}
	
	public static function getYear()
	{
		return mt_rand(1980, 2020);
	}
	
	public static function getMysqlDate()
	{
		$year = mt_rand(1980, 2020);
		$month = mt_rand(1, 12);
		$day = mt_rand(1, cal_days_in_month(CAL_GREGORIAN, $month, $year));
		return $year . '-' . $month . '-' . $day;
	}
	
	public static function getMysqlTime()
	{
		return mt_rand(0, 23) . ':' . mt_rand(0, 59) . ':' . mt_rand(0, 59);
	}
	
	public static function getMysqlDateTime()
	{
		return static::getMysqlDate() . ' ' . static::getMysqlTime();
	}
}
