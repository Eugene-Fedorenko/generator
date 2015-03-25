<?php

class GeneratorApp
{
	private $_config;
	private static $_instance;
	
	public static function instance($config = null)
	{
		if (empty(self::$_instance)) {
			self::$_instance = new self($config);
		}
		
		return self::$_instance;
	}
	
	protected function __construct($config)
	{
		$this->_config = $config;
	}
	
	public function run($params)
	{
		$controllerName = $this->_config['defaultController'];
		$actionName = $this->_config['defaultAction'];
		if (!empty($params['c']) && is_string($params['c'])) {
			$route = explode('/', $params['c']);
			
			$controllerName = $route[0];
			if (!empty($route[1])) {
				$actionName = $route[1];
			}
		}
		
		$className = ucfirst(strtolower($controllerName)) . 'Controller';
		$methodName = ucfirst(strtolower($actionName)) . 'Action';
		
		if (!class_exists($className)) {
			throw new Exception('Unknown command');
		}
		
		$controller = new $className($params);
		
		if (!method_exists($controller, $methodName)) {
			throw new Exception('Unknown subcommand');
		}
		
		return $controller->$methodName();
	}
}
