<?php
namespace app\controllers;

use app\models\AppTokenModel;
use user\models\IdentityModel;

class AppTokenController extends AuthController
{
	protected $noAuthActions = ['code'];
	protected $access = [
			'code' => ['student'],
			
	];
	protected $filter = [
			'code' => [
					'*' => [
							'require' => ['app_key'],
					]
			],
			'accessToken' => [
					'*' => [
							'require' => ['code']
					]
			],
			'refreshToken' => [
					'*' => [
							'require' => ['refresh_token']
					]
			]
	];
	
	public function actionCode()
	{
		$identity = $this->user->getIdentity();
		$app_id = self::getAppInfoByKey($this->params['app_key'], 'id');
		
		$appTokenModel = new AppTokenModel();
		$res = $appTokenModel->_set($app_id, $identity['id'], ['group' => $identity['group']]);
		$res = $res ? ['code' => $res['refresh_token'], 'expired_in' => $res['expired_in']] : null;
		
		return $this->response($res, $appTokenModel);
	}
	
	public function actionAccessToken()
	{
		$this->params['refresh_token'] = $this->params['code'];
		return $this->actionRefreshToken();
	}
	
	public function actionRefreshToken()
	{
		$app_id = $this->getAppInfo('id');
		
		$appTokenModel = new AppTokenModel();
		$res = $appTokenModel->refresh($app_id, $this->params['refresh_token']);
		
		if (isset($res['access_token'])) {
			$res['profile'] = $this->getClientProfile($res['access_token']);
		}
		
		return $this->response($res, $appTokenModel);
	}
	
	protected function getClientProfile($access_token)
	{
		$tokenInfo = AppTokenModel::getByAccessToken($this->getAppInfo('id'), $access_token);
		if (!$tokenInfo) {
			return null;
		}
		$identity = IdentityModel::findById([$tokenInfo['owner_id'], IdentityModel::GROUP_STUDENT]);
		if (!$identity) {
			return null;
		}
		
		return $identity->profile();
	}
}