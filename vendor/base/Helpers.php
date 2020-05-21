<?php
namespace vendor\base;

use vendor\exceptions\InvalidParamException;

class Helpers
{
	public static function when($when, $data)
	{
		//and =
		if (isset($when['when'])) {
			foreach ($when['when'] as $f => $tv) {
				if (!key_exists($f, $data) || $data[$f] != $tv) {
					return false;
				}
			}
		}
		//or <>
		if (isset($when['!when'])) {
			foreach ($when['!when'] as $f => $tv) {
				if (key_exists($f, $data) && $data[$f] == $tv) {
					return false;
				}
			}
		}
		return true;
	}
	
	/**
	 * @throws \InvalidArgumentException
	 * @return boolean 返回是否进行了回调
	 */
	public static function callback(&$res, array $config, array $data = [], $caller = null)
	{
		if (!isset($config['callback'])) {
			throw new InvalidParamException(__METHOD__, 'config');
		}
		
		if (!self::when($config, $data)) {
			return false;
		}
		
		//处理回调
		$callback = $config['callback'];
		if (isset($config['instance']) && $config['instance']) {
			if (!is_array($callback) || !isset($callback[0]) || !class_exists($callback[0])) {
				throw new InvalidParamException(__METHOD__, 'config');
			}
			$callerClass = $callback[0];
			$callback[0] = $caller instanceof $callerClass ? $caller : new $callerClass;
		}
		if (!is_callable($callback)) {
			throw new InvalidParamException(__METHOD__, 'config');
		}
		
		$args = [];
		if (isset($config['args'])) {
			foreach ((array)$config['args'] as $arg) {
				$args[] = $data[$arg] ?? null;
			}
		}
		
		$res = call_user_func_array($callback, $args);
		return true;
	}
}