<?php
namespace posts\controllers;

use posts\models\PostCategoryModel;
use common\controllers\CategoryController;

class PostCategoryController extends CategoryController
{
	protected $access = [
			'*' => ['admin'],
			'list' => ['*'],
			'view' => ['*'],
			'sectionList' => ['*'],
			'navList' => ['*'],
	];
	protected $filter = [
			'list' => [
					'*' => [
							'default' => [
									'pid' => '',
									'all' => 0,
							]
					],
			],
			'adminList' => [
					'*' => [
							'default' => [
									'pid' => '',
									'status' => null,
									'order' => [],
									'all' => 0,
							]
					],
			],
			'*' => [
					'*' => [
							'require' => ['id'],
					],
			],
			'create' => [],
			'view' => [
					'*' => [
							'require' => ['id'],
					],
			],
			'setOrder' => [
					'*' => [
							'require' => ['id', 'target_id'],
					],
			],
			'sectionList' => [
					'*' => [
							'require' => ['section'],
							'default' => [
									'limit' => -1,
							],
					],
			],
			'navList' => [
					'*' => [
							'default' => [
									'limit' => -1,
							],
					],
			],
	];

	protected $model = PostCategoryModel::class;

	public function actionSectionList()
	{
		$model = new PostCategoryModel();
		$res = $model->get_by_section(
				$this->params['section'], 
				$this->params['limit'],
				PostCategoryModel::STATUS_ACTIVE
			);
		return $this->response($res);
	}
	
	public function actionNavList()
	{
		$model = new PostCategoryModel();
		$res = $model->get_section_nav(
				$this->params['limit'],
				PostCategoryModel::STATUS_ACTIVE,
				true
		);
		return $this->response($res);
	}

}