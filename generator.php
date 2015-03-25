#!/usr/bin/php
<?php

require_once dirname(__FILE__) . '/lib/autoloader.php';

$config = array(
	'defaultController' => 'help',
	'defaultAction' => 'index'
);

$params = getopt('c:u:p:h:n:t:r:s');

$app = GeneratorApp::instance($config);

try {
	echo $app->run($params);
} catch (Exception $e) {
	echo 'Error happens: ' . $e->getMessage() . "\n";
}
