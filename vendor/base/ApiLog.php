<?php
namespace vendor\base;

use vendor\exceptions\InvalidConfigException;

/**
 * 
 * @property \vendor\base\User $user
 *
 */
class ApiLog
{
	use AppTrait;
	
	/**
	 * @var ApiDataInterface
	 */
	protected $apiModel = null;
	/**
	 * @var ApiLogDataInterface
	 */
	protected $logModel = null;
	
	public $enable = false;
	
	public function __construct($config)
	{
		if (!isset($config['apiModel'], $config['logModel'])) {
			throw new InvalidConfigException();
		}
		
		$this->apiModel = new $config['apiModel'];
		$this->logModel = new $config['logModel'];
		
		if (!$this->apiModel instanceof ApiDataInterface || !$this->logModel instanceof ApiLogDataInterface) {
			throw new InvalidConfigException();
		}
		
		if (isset($config['enable'])) {
			$this->enable = !!$config['enable'];
		}
	}
	
	protected function register(&$name = '', $enable_log = 0)
	{
		$runningModule = \App::getRunningModule();
		$md5 = $this->apiModel->_set(
				$runningModule->getModuleName(),
				$runningModule->getRunningControllerName(), 
				$runningModule->getRunningActionName(), 
				$name,
				$enable_log
		);
		
		return $md5;
	}
	
	public function write(BaseResponse $response)
	{
		$runningModule = \App::getRunningModule();
		$runningController = $runningModule->getRunningController();
		$behavior = $runningController->getActionBehavior();
		
		$statusCode = $response->getStatusCode();
		$name = isset($behavior['name']) ? $behavior['name'] : '';
		$enable_log = isset($behavior['enable_log']) ? $behavior['enable_log'] : 0;
		
		$md5 = $this->register($name, $enable_log);
		
		if (!$md5) {
			return false;
		}
		
		$identity = $this->user->getIdentity();
		
		$res = $this->logModel->_set($md5, $behavior['name'], $behavior['note'],
				$this->user->getRole(), $identity ? $identity['id'] : null, $identity ? $identity['username'] : null, 
				$statusCode >= 200 && $statusCode < 300, $this->request->getUserIP());
		
		return !!$res;
	}
}