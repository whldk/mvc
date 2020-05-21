<?php
namespace vendor\base;

use vendor\exceptions\InvalidConfigException;
use vendor\exceptions\ServerErrorException;

abstract class JsonFile
{
	private static $cache = [];
	
	const FILE = '';
	
	const KEY = '';
	
	const DIR = DIR_STATIC . '/files';
	
	public function __construct()
	{
		if (!static::FILE || !static::KEY) {
			throw new InvalidConfigException();
		}
	}
	
	private static function file()
	{
		return self::DIR . '/' . static::FILE . '.json';
	}
	
	private static function _read()
	{
		if (!isset(self::$cache[static::FILE])) {
			self::$cache[static::FILE] = file_exists(self::file()) ? (json_decode(file_get_contents(self::file()), true) ?: []) : [];
		}
		return self::$cache[static::FILE];
	}
	
	private static function _write()
	{
		if (isset(self::$cache[static::FILE])) {
			if (!is_dir(self::DIR) && !mkdir(self::DIR, DIR_MODE)) {
				throw new ServerErrorException('Cannot make dir: ' . self::DIR);
			}
			file_put_contents(self::file(), json_encode(self::$cache[static::FILE], JSON_UNESCAPED_UNICODE));
		}
	}
	
	public static function json_get($id = null)
	{
		$data = self::_read();
		return !$id ? $data : (isset($data[$id]) ? $data[$id] : []);
	}
	
	public function json_set($id, $vals)
	{
		$vals = (array)$vals;
		$vals[static::KEY] = $id;
		
		if (!$vals) {
			return false;
		}
		
		self::_read();
		
		if (isset(self::$cache[static::FILE][$id])) {
			self::$cache[static::FILE][$id] = $vals + self::$cache[static::FILE][$id];
		} else {
			self::$cache[static::FILE][$id] = $vals;
		}
		
		return true;
	}
	
	public function json_del($id)
	{
		self::_read();
		unset(self::$cache[static::FILE][$id]);
		return true;
	}
	
	public function __destruct()
	{
		self::_write();
		//清理缓存（以每个实例的运行周期为清理周期）
		unset(self::$cache[static::FILE]);
	}
}