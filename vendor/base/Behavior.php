<?php
namespace vendor\base;

/**
 * @property \vendor\base\User $user
 * @property \vendor\base\Response $response
 * @property \vendor\base\Request $request
 */
class Behavior
{
	use AppTrait;
	
	protected $controller;
	protected $params;
	
	public function __construct(Controller $controller)
	{
		$this->controller = $controller;
		$this->params = $controller->params();
	}
}