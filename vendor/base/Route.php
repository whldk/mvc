<?php
namespace vendor\base;

class Route
{
	public $module = '';
	public $controller = '';
	public $action = '';
	
	protected $default_module = '';
	protected $default_controller = '';
	protected $default_action = '';
	
	public function __construct($config = [])
	{
		foreach ($config as $k => $v) {
			switch ($k) {
				case 'default_module' : 
				case 'default_controller' :
				case 'default_action' :
					$v = strval($v);
					$this->$k = trim(strtolower($v));
					break;
			}
		}
	}
	
	/**
	 * @param BaseRequest $request
	 */
	public function resolve($request)
	{
		$this->module = $this->default_module;
		$this->controller = $this->default_controller;
		$this->action = $this->default_action;
		
		$path = $request->getPathinfo();
		if (!$path) {
			$route = [];
		} else {
			$route = explode('/', trim(strtolower($path), '/'));
		}
		
		switch (count($route)) {
			case 0 : 
				break;
			case 1 :
				$this->controller = $route[0];
				break;
			case 2 : 
				list($this->controller, $this->action) = $route;
				break;
			default : 
				list($this->module, $this->controller, $this->action) = $route;
				break;
		}
		$this->module = strtolower($this->module);
		$this->controller = strtolower($this->controller);
		$this->action = strtolower($this->action);
		
		return [$this->module, $this->controller, $this->action];
	}
	
}