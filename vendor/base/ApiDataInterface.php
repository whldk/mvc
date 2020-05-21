<?php
namespace vendor\base;

interface ApiDataInterface
{
	public function _set($module, $controller, $action, &$name);
}