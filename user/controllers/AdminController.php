<?php
namespace user\controllers;

use vendor\base\Controller;
use user\models\AdminModel;

class AdminController extends Controller
{
	protected $access = [
			'*' => ['admin'],
	];

	protected $filter = [
			'search' => ['*' => ['default' => ['status' => AdminModel::STATUS_ACTIVE, 'search' => []]]],
			'view' => ['*' => ['require' => ['id']]],
			'update' => ['*' => ['require' => ['id']]],
			'deactive' => ['*' => ['require' => ['id']]],
			'delete' => ['*' => ['require' => ['id']]],
	];

	public $behavior = '\user\behaviors\AdminBehavior';

	public function actionSearch()
	{
		parent::page();
		$res = AdminModel::_list($this->params['status'], $this->params['search'], $this->pagesize, $this->page);
		return $this->response($res);
	}
	
	public function actionView()
	{
		$res = AdminModel::_get($this->params['id']);
		return $this->response($res);
	}
	
	public function actionCreate()
	{
		$model = new AdminModel();
		$res = $model->_set($this->params);
		return $this->response($res, $model, 201);
	}
	
	public function actionUpdate()
	{
		$model = new AdminModel();
		$res = $model->_set($this->params);
		return $this->response($res, $model, 204);
	}
	
	public function actionDeactive()
	{
		$model = new AdminModel();
		$res = $model->deactive($this->params['id']);
		return $this->response($res, $model, 204);
	}
	
	public function actionDelete()
	{
		$model = new AdminModel();
		$res = $model->del_one($this->params);
		return $this->response($res, $model, 204);
	}
}