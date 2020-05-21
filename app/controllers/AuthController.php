<?php
namespace app\controllers;

use vendor\base\Controller;
use vendor\api\ApiUtil;
use app\models\AppModel;
use vendor\exceptions\AuthException;


/**
 * Base controller for auth module 
 * @property \vendor\base\Request $request
 */
abstract class AuthController extends Controller
{
	protected $noAuthActions = [];
	
	private $appKey;
	private $appInfo;
	
	/**
	 * @throws AuthException
	 * @return boolean
	 * @see \vendor\base\Controller::beforeRun()
	 */
	protected function beforeRun()
	{
		if (parent::beforeRun() === true) {
			return $this->authorize();
		}
		
		return false;
	}
	
	/**
	 * @throws AuthException
	 * @return boolean
	 */
	protected function authorize()
	{
		if (in_array($this->action, $this->noAuthActions)) {
			return true;
		}
		
		$request = $this->request;
		
		$method = $request->getMethod();
		$path = $request->getPath();
		$params = $request->getQueryParams();
		$headers = $request->getHeaders();
		$body = $request->getRawBody();
		
		$authHeader = $request->getHeaders('Authorization');
		if ($authHeader === null || strpos($authHeader, ':') === false) {
			throw new AuthException(null, 'auth failed');
		}
		
		list($this->appKey, $signature) = explode(':', $authHeader, 2);
		$appSecret = $this->getAppInfo('app_secret');
		
		$res = ApiUtil::authorize($signature, $appSecret, $method, $path, $params, $headers, $body);
		if ($res !== ApiUtil::ERR_NONE) {
			throw new AuthException(null, 'auth failed');
		}
		
		return true;
	}
	
	protected function getAppInfo($name = null)
	{
		if ($this->appInfo === null) {
			$this->appInfo = AppModel::getByAppKey($this->appKey, true) ?: [];
		}
		
		if ($name !== null) {
			return isset($this->appInfo[$name]) ? $this->appInfo[$name] : null;
		}
		
		return $this->appInfo;
	}
	
	protected static function getAppInfoByKey($appKey, $name = null)
	{
		$appInfo = AppModel::getByAppKey($appKey, false) ?: [];
		
		if ($name !== null) {
			return isset($appInfo[$name]) ? $appInfo[$name] : null;
		}
		
		return $appInfo;
	}
}