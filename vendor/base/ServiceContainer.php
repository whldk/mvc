<?php
namespace vendor\base;

use vendor\exceptions\InvalidConfigException;
use vendor\exceptions\InvalidParamException;

/**
 * 一种container类一个运行实例
 */
abstract class ServiceContainer
{
	private static $instances;
	
	public $componentsNamespace = '\vendor\base';
	protected $components = [];
	
	protected function __construct($config = [])
	{

		$config = (array)$config;
		
		if (!isset($config['components'])) {
			return ;
		}
		$this->registerComponents((array)$config['components'], $config);
		
		if (!isset($config['instances'])) {
			return ;
		}
		$this->registerInstances((array)$config['instances'], $config);
	}
	
	protected function registerComponents(array $components, array $config)
	{
		foreach ($components as $component) {
			if (isset($config[$component]) && is_object($config[$component])) {
				$this->components[$component] = $config[$component];
			} else {
				isset($config[$component]['class']) ?: $config[$component]['class'] = $this->componentsNamespace . '\\' . ucfirst($component);
				if (class_exists($config[$component]['class'])) {
					$this->components[$component] = $config[$component];
				} else {
					throw new InvalidConfigException('app config error');
				}
			}
		}
	}
	
	protected function registerInstances(array $instances, array $config)
	{
		foreach ($instances as $instance) {
			if (isset($config[$instance]) && is_object($config[$instance])) {
				$this->instances[get_class($config[$instance])] = $config[$instance];
			} else {
				if (!isset($config[$instance]['class'])) {
					throw new InvalidConfigException('app config error');
				}
				$class = $config[$instance]['class'];
				if (!class_exists($class) || !in_array(self::class, class_parents($class))) {
					throw new InvalidConfigException('app config error');
				}
				$this->instances[$class::getClass()] = $config[$instance];
			}
		}
	}
	
	/**
	 * 如果实例已存在，不会重新创建
	 */
	public static function getInstance($config = [])
	{
		$staticClass = static::class;
		if (isset(self::$instances[$staticClass])) {
			return static::instantiate(self::$instances[$staticClass]);
		}
		return self::$instances[$staticClass] = new $staticClass($config);
	}
	
	public function __get($name)
	{
		if (isset($this->components[$name])) {
			if (!is_object($this->components[$name])) {
				try {
					$this->components[$name] = self::instantiate($this->components[$name]);
				} catch (\Exception $e) {
					return null;
				}
			}
			return $this->components[$name];
		}
		return null;
	}
	
	public function __set($name, $value)
	{
		$this->components[$name] = $value;
	}
	
	public static function instantiate(&$component)
	{
		if (is_object($component)) {
			return $component;
		}
		if (!isset($component['class']) || !class_exists($component['class'])) {
			throw new InvalidParamException(__METHOD__, 'component');
		}
		/* @var ServiceContainer $class */
		$class = $component['class'];
		unset($component['class']);
		$component = new $class($component);
		
		return $component;
	}
	
	public static function getClass()
	{
		return static::class;
	}
}