<?php

function autoload($class)
{
	$path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
	if (is_file($path)) {
		require_once $path;
	}
}

spl_autoload_register('autoload');

//third party 加载第三方组件
require_once __DIR__ . '/vendor/html_purifier/HTMLPurifier.auto.php';
require_once __DIR__ . '/vendor/excel/PHPExcel/Autoloader.php';