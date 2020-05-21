<?php
namespace posts\controllers;

use user\models\AdminModel;
use vendor\base\Controller;
use posts\models\PostModel;
use posts\models\PostCategoryModel;
use vendor\base\Upload;
use vendor\base\UploadManager;
use vendor\helpers\ArrayHelper;
use user\models\SchoolUserModel;

class PostController extends Controller
{
	public static $adminRoles = ['admin'];
	
	protected $access = [
			'*' => ['admin'],
            'upload' => ['*'],
            'download' => ['admin', 'temp_user'],
            'sectionList' => ['*'],
			'list' => ['*'],
			'view' => ['*']
	];

	protected $filter = [
			'*' => [
					'*' => [
							'require' => ['id'],
					]
			],
            'upload' => [
                '*' => [
                    'require' => ['dir'],
                    'range' => ['dir' => ['posts', 'document']],
                    'default' => [
                        'mime' => null
                    ]
                ]
            ],
			'sectionList' => [
					'*' => [
							'require' => ['section'],
							'default' => ['order' => []],
					],
			],
			'list' => [
					'*' => [
							'default' => [
							        'cid' => null,
									'search' => [],
									'order' => []
							],
					],
			],
			'adminList' => [
					'*' => [
							'default' => [
									'cid' => null,
									'status' => null, 
									'pend' => null, 
									'search' => [], 
									'order' => []
							],
					], 
			],
			'save' => [
					'*' => [
							'default' => [
									'id' => null
							],
							'range' => [
									'id' => 'canEdit'
							]
					]
			],
			'top' => [
					'*' => [
							'require' => ['id'],
							'default' => ['cancel' => false]
					],
			],
			'delete' => [
					'*' => [
							'require' => ['id'],
							'range' => [
								//	'id' => 'canDel'
							]
					],
			],
            'download' => [
                '*' => [
                    'require' => ['url']
                ]
            ]
	];

	public function actionDownload()
    {
        $postModel = new PostModel();
        $postModel->downLoad($this->params['url']);
        $postModel->incrViewCount($this->params['id'], 'download_count');
    }

    public function actionUpload()
    {
        $dir = $this->params['dir'] . DIRECTORY_SEPARATOR;
        $upload = new UploadManager($dir);
        $res = $upload->upload(null, null, [], null, false, false);
        if ($res === false) {
            $errors = $upload->errors();
            if (isset($errors[Upload::ERR_UPLOAD]) || isset($errors[Upload::ERR_MIME])) {
                $this->response->setStatusCode(400);
            } else {
                $this->response->setStatusCode(500);
            }
            $res = ['error' => $errors];
        } else {
            $this->response->setStatusCode(201);
            $res = [];
        }

        $res['urls'] = $upload->getUrls();

        return $res;
    }

	public function actionSectionList()
	{
		$ctgrModel = new PostCategoryModel();
		$category = $ctgrModel->get_by_section(
				$this->params['section'],
				1,
				PostCategoryModel::STATUS_ACTIVE
		);
		if (!$category) {
			return $this->response([]);
		}
		
		$res = [];
		$res['category'] = $category[0];
		self::page(true);
		$postModel = new PostModel();
		$res['posts'] = $postModel->_list(
				$category[0]['id'], 
				PostModel::STATUS_PUB, 
				null,
				null,
				$this->params['order'],
				$this->pagesize,
				$this->page
			);

		return $this->response($res);
	}
	
	public function actionList()
	{
		parent::page(true);
		$postModel = new PostModel();
        $res = $postModel->recursive_list(
            $this->params['cid'],
            PostModel::STATUS_PUB,
           null,
            $this->params['search'],
            $this->params['order'],
            $this->pagesize,
            $this->page
        );

		return $this->response($res);
	}
	
	public function actionAdminList()
	{
		parent::page(true);
		$postModel = new PostModel();
		$res = $postModel->recursive_list(
				$this->params['cid'],
				$this->params['status'],
				$this->params['pend'],
				$this->params['search'],
				$this->params['order'],
				$this->pagesize,
				$this->page
		);

      	if ($res['_list']) {
			$users = ArrayHelper::index(AdminModel::_get(array_column($res['_list'], 'author_id'), ['id', 'group', 'username', 'realname']), 'id');
            ArrayHelper::array_set_col($res['_list'], $users, 'author_id', 'id');
      	}
		
		return $this->response($res);
	}
	
	public function actionSave()
	{
		//create or update
		if (!isset($this->params['id'])) {
			$identity = $this->user->getIdentity();
			$this->params['author_id'] = $identity['id'];
			$responseCode = 201;
		} else {
			$responseCode = 201;
		}

		$postModel = new PostModel();
		$res = $postModel->edit($this->params['id'], $this->params);
		if ($res !== null && isset($this->params['status']) && $this->params['status'] == PostModel::STATUS_PUB) {
			$post_id = isset($this->params['id']) ? $this->params['id'] : (isset($res['id']) ? $res['id'] : null);
			if ($post_id) {
// 				if (in_array($this->user->getRole(), ['school_admin'], true)) {
					$postModel->publish($post_id);
// 				} else {
// 					$postModel->pend($post_id, PostModel::STATUS_PUB);
// 				}
			}
		}
		
		return $this->response($res, $postModel, $responseCode);
	}
	
	public function actionPub()
	{
		$postModel = new PostModel();
// 		if (in_array($this->user->getRole(), ['school_admin'], true)) {
			$res = $postModel->publish($this->params['id']);
// 		} else {
// 			$res = $postModel->pend($this->params['id'], PostModel::STATUS_PUB);
// 		}
		
		return $this->response($res, $postModel, 201);
	}
	
	public function actionUnPub()
	{
		$postModel = new PostModel();
// 		if (in_array($this->user->getRole(), ['school_admin'], true)) {
			$res = $postModel->unPublish($this->params['id']);
// 		} else {
// 			$res = $postModel->pend($this->params['id'], PostModel::STATUS_UNPUB);
// 		}
		
		return $this->response($res, $postModel, 201);
	}
	
	public function actionTop()
	{
		$postModel = new PostModel();
        $res = $postModel->top((string)$this->params['id'], !!$this->params['cancel']);
		return $this->response($res, $postModel, 200);
	}
	
	public function actionView()
	{
		$identity = $this->user->getIdentity();
		
		if ($identity && in_array($this->user->getRole(), self::$adminRoles, true)) {
			$status = null;
		} else {
			$status = PostModel::STATUS_PUB;
		}
		
		$postModel = new PostModel();
		$res = $postModel->_get($this->params['id'], $status);
		if ($res) {
			$res['adjacent'] = $postModel->get_adjacent($res[0]['cid'], $res[0]['final_order'], $status);
		}
		
		$postModel->incrViewCount($this->params['id']);
		
		return $this->response($res);
	}

	public function actionDelete()
	{
		$postModel = new PostModel();
		$res = $postModel->del($this->params['id']);
		return $this->response($res, $postModel, 201);
	}
	

	protected function canEdit()
	{
		$identity = $this->user->getIdentity();

		if (!isset($this->params['id'])) {
			$this->params['author_id'] = $identity['id'];
			$this->params['id'] = null;
			return true;
		}

        return true;
//		$post = PostModel::_get($this->params['id'], PostModel::STATUS_UNPUB, null, ['author_id']);
//
//
//		if (!$post) {
//			return false;
//		}

//		if (in_array($this->user->getRole(), ['admin'], true) ||
//			$post[0]['author_id'] === $identity['id']) {
//			return true;
//		}
//
//		return false;
	}
	
	protected function canDel()
	{
		$post = PostModel::_get($this->params['id'], PostModel::STATUS_UNPUB, null, ['author_id']);
		if (!$post) {
			return false;
		}
		
		$identity = $this->user->getIdentity();
		if (in_array($this->user->getRole(), ['school_admin'], true) || 
			$post[0]['author_id'] === $identity['id']) {
			return true;
		}
		
		return false;
	}
}