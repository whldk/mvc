<?php
namespace app\controllers;

use vendor\base\Controller;
use app\models\AppModel;

/**
 * 
 * must transfer in ssl
 */
class AppController extends Controller
{
	protected $access = [
			'*' => ['admin'],
			'info' => ['*'],
	];
	protected $filter = [
			'search' => ['*' => ['default' => ['status' => AppModel::STATUS_ACTIVE, 'search' => []]]],
			'view' => ['*' => ['require' => ['id']]],
			'info' => ['*' => ['require' => ['app_key']]],
			'approve' => ['*' => ['require' => ['id']]],
			'deactivate' => ['*' => ['require' => ['id']]],
	];
	
	public function actionSearch()
	{
		parent::page(true);
		$model = new AppModel();
		$res = $model->_list($this->params['status'], $this->params['search'], $this->pagesize, $this->page);
		return $this->response($res, $model, 200);
	}
	
	public function actionCreate()
	{
		$model = new AppModel();
		$res = $model->_set(null, $this->params);
		return $this->response($res, $model, 201);
	}
	
	public function actionView()
	{
		$model = new AppModel();
		$res = $model->_get($this->params['id']);
		return $this->response($res);
	}
	
	public function actionInfo()
	{
		$res = AppModel::getByAppKey($this->params['app_key'], false);
		return $this->response($res);
	}
	
	public function actionApprove()
	{
		$model = new AppModel();
		$res = $model->approve($this->params['id']);
		return $this->response($res, $model, 204);
	}
	
	public function actionDeactivate()
	{
		$model = new AppModel();
		$res = $model->deactivate($this->params['id']);
		return $this->response($res, $model, 204);
	}
}