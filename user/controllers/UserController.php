<?php
namespace user\controllers;

use vendor\base\Controller;
use user\models\SchoolUserModel;

class UserController extends Controller
{
	protected $access = [
			'*' => ['admin'],
			'avatar' => ['@'],
	];
	
	protected $filter = [
			'search' => [ 
					'*' => [ 
							'default' => [ 
									'school_id' => null,
									'group' => null,
									'status' => SchoolUserModel::STATUS_ACTIVE,
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
			'avatar' => [ 
					'*' => [ 
							'require' => [ 
									'zoom',
									'x',
									'y',
									'w',
									'h'
							],
							'require-file' => [ 
									'avatar'
							]
					]
			],
	];

    public $behavior = '\user\behaviors\UserBehavior';

	public function actionSearch()
	{
		parent::page();
		$res = SchoolUserModel::_list($this->params['school_id'], $this->params['group'], $this->params['status'], $this->params['search'], $this->pagesize, $this->page);
		return $this->response($res);
	}
	
	public function actionView()
	{
		$res = SchoolUserModel::_get($this->params['id']);
		return $this->response($res);
	}
	
	public function actionUpdate()
	{
		$model = new SchoolUserModel();
		$res = $model->_set($this->params);
		return $this->response($res, $model, 204);
	}
	
	public function actionDelete()
	{
		$model = new SchoolUserModel();
		
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
	
	public function actionAvatar()
	{
		$identity = $this->user->getIdentity();
		$model = new SchoolUserModel();
		$res = $model->set_avatar($identity['id'], $this->params['zoom'], $this->params['x'], $this->params['y'], $this->params['w'], $this->params['h']);
		return $this->response($res, $model, 200);
	}
}