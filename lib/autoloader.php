<?php

function __autoload($class)
{
	$matches = [];
	if ($class == 'GeneratorApp') {
		$path = 'app.php';
	} else if ($class == 'Controller') {
		$path = 'controller.php';
	} else if ($class == 'Model') {
		$path = 'model.php';
	} else if (
		preg_match('/^(\w*)Controller$/', $class, $matches)
	) {
		$path = 'controllers/' . strtolower($matches[1]) . '.php';
	} else if (
		preg_match('/^(\w*)Model$/', $class, $matches)
	) {
		$path = 'models/' . strtolower($matches[1]) . '.php';
	} else {
		$path = 'components/' . strtolower($class) . '.php';
	}
	
	$path = dirname(__FILE__) . '/' . $path;
	
	if (file_exists($path)) {
		require_once $path;
	}
}