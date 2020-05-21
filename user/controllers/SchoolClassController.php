<?php
namespace user\controllers;

use vendor\base\Controller;
use user\models\SchoolClassModel;
use user\models\TeacherClassModel;

class SchoolClassController extends Controller
{
	protected $access = [
			'*' => ['school_admin'],
			'search' => ['school_admin', 'teacher'],
			'view' => ['school_admin', 'teacher'],
			'options' => ['school_admin', 'teacher']
	];
	protected $filter = [
			'search' => [ 
					'*' => [ 
							'default' => [ 
									'grade' => null,
									'status' => SchoolClassModel::STATUS_ACTIVE,
									'search' => []
							]
					]
			],
			'options' => [ 
					'*' => [ 
							'default' => [ 
									'search' => []
							]
					]
			],
			'*' => [ 
					'*' => [ 
							'require' => [ 
									'id'
							]
					]
			],
			'deactivate' => [
					'*' => [
							'require' => [
									'grade'
							]
					]
			],
			'addTeacher' => [ 
					'*' => [ 
							'require' => [ 
									'id',
									'user_id'
							]
					]
			],
			'delTeacher' => [ 
					'*' => [ 
							'require' => [ 
									'id',
									'user_id'
							]
					]
			],
            'create' => []
	];

    public $behavior = '\user\behaviors\SchoolClassBehavior';

	public function actionSearch()
	{
		parent::page();
		$res = SchoolClassModel::_list(
				$this->params['grade'], $this->params['status'], 
				$this->params['search'], $this->pagesize, $this->page
		);
		return $this->response($res);
	}
	
	public function actionOptions()
	{
		parent::page();
		$res = SchoolClassModel::options(
				$this->params['search'], $this->pagesize, $this->page
		);
		return $this->response($res);
	}
	
	public function actionView()
	{
		$res = SchoolClassModel::_get($this->params['id']);
		return $this->response($res);
	}
	
	public function actionCreate()
	{
		$model = new SchoolClassModel();
		$res = $model->_set($this->params);
		return $this->response($res, $model, 201);
	}
	
	public function actionUpdate()
	{
		$model = new SchoolClassModel();
		$res = $model->_set($this->params);
		return $this->response($res, $model, 204);
	}
	
	public function actionDelete()
	{
		$model = new SchoolClassModel();
		$res = $model->del_one($this->params['id']);
		return $this->response($res, $model, 204);
	}

	public function actionDeactivate()
	{
		$identity = $this->user->getIdentity();
		$model = new SchoolClassModel();
		$res = $model->deactivate($identity['school_id'], $this->params['grade']);
		return $this->response($res, $model, 204);
	}
	
	public function actionAddTeacher()
	{
		$model = new TeacherClassModel();
		$res = $model->_set($this->params['id'], $this->params['user_id']);
		return $this->response($res, $model, 201);
	}
	
	public function actionDelTeacher()
	{
		$model = new TeacherClassModel();
		$res = $model->del_one(['class_id' => $this->params['id'], 'user_id' => $this->params['user_id']]);
		return $this->response($res, $model, 204);
	}
}