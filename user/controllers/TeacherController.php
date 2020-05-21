<?php
namespace user\controllers;

use vendor\base\Controller;
use user\models\SchoolUserModel;
use user\models\TeacherModel;

class TeacherController extends Controller
{
	protected $access = [
			'search' => ['school_admin', 'teacher'],
			'view' => ['*'],
			'*' => ['school_admin'],
	];
	protected $filter = [
			'search' => [ 
					'*' => [ 
							'default' => [ 
									'status' => SchoolUserModel::STATUS_ACTIVE,
									'search' => []
							]
					]
			],
			'create' => [],
			'*' => [ 
					'*' => [ 
							'require' => [ 
									'id'
							],
					]
			],
	];

    public $behavior = '\user\behaviors\TeacherBehavior';

    public function actionSearch()
	{
		parent::page();
		$res = TeacherModel::_list(
			null, null, 
            $this->params['status'],
            $this->params['search'],
            $this->pagesize,
            $this->page
		);
		return $this->response($res);
	}
	
	public function actionCreate()
	{
		$model = new TeacherModel();
		$res = $model->_set($this->params);
		return $this->response($res, $model, 201);
	}
	
	public function actionView()
	{
		$res = TeacherModel::_get($this->params['id']);
		return $this->response($res);
	}
	
	public function actionUpdate()
	{
		$model = new TeacherModel();
		$res = $model->_set($this->params);
		return $this->response($res, $model, 204);
	}
	
	public function actionDelete()
	{
		$model = new TeacherModel();
		
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
	
}