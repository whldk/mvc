<?php
namespace user\controllers;

use user\models\SchoolUserModel;
use user\models\StudentModel;
use user\models\IdentityModel;
use user\models\StudentClassModel;
use user\models\SchoolClassModel;

class StudentController extends UserController
{
	protected $access = [
			'*' => ['school_admin', 'teacher'],
			'delete' => ['school_admin'],
	];
	protected $filter = [
			'search' => [ 
					'*' => [ 
							'default' => [ 
									'status' => SchoolUserModel::STATUS_ACTIVE,
									'class_id' => null,
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
			'create' => [ 
					'*' => [ 
							'require' => [ 
									'class_id'
							],
					]
			],
			'update' => [ 
					'*' => [ 
							'require' => [ 
									'id'
							],
							'range' => [ 
									'id' => 'access_user'
							]
					]
			],
			'setClass' => [
					'*' => [ 
							'require' => [ 
									'class_id',
									'id'
							],
					]
			],
	];

    public $behavior = '\user\behaviors\StudentBehavior';

	public function actionSearch()
	{
		parent::page();
		$res = StudentModel::student_list($this->params['class_id'], $this->params['status'], $this->params['search'], $this->pagesize, $this->page);
		return $this->response($res);
	}
	
	public function actionCreate()
	{
		$model = new StudentModel();
		$res = $model->_set($this->params);
		return $this->response($res, $model, 201);
	}
	
	public function actionView()
	{
		$res = StudentModel::_get($this->params['id']);
		if ($res) {
			$res = StudentModel::hasMultis($res, SchoolClassModel::class, null, true);
		}
		return $this->response($res);
	}
	
	public function actionSetClass()
	{
		$model = new StudentClassModel();
		$res = $model->_set($this->params['id'], $this->params['class_id'], $this->params);
		return $this->response($res, $model, 204);
	}
	
	public function actionDelClass()
	{
		$model = new StudentClassModel();
		$res = $model->_del($this->params['id'], $this->params['class_id']);
		return $this->response($res, $model, 204);
	}
	
	public function actionDelete()
	{
		$model = new StudentModel();
		
		$res = [];
		$deleted = [];
		foreach ((array)$this->params['id'] as $id) {
			if (in_array($id, $deleted)) {
				$res[] = ['_status' => 1];
				continue;
			}
			
			$tmp = $model->del_one($id);
			if ($tmp === null) {
				$res[] = ['_status' => 0, 'error' => $model->errors()];
			} else {
				$deleted[] = $id;
				$res[] = ['_status' => 1];
			}
		}
		
		return $this->response($res, $model, 200);
	}
	
	protected function access_user()
	{
		/*@var $identity \user\models\IdentityModel */
		$identity = $this->user->getIdentity();
		return $identity->canAccessUsers($this->params['id'], IdentityModel::GROUP_STUDENT);
	}
}