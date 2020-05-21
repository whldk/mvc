<?php
namespace controllers;

use vendor\base\Controller;
use user\models\BatchSignModel;
use user\models\SchoolUserModel;
use user\models\LoginModel;
use user\models\SchoolModel;
use user\models\TempUserModel;
use vendor\sdk\IlabJwt;
use vendor\exceptions\HttpException;

class SiteController extends Controller
{
    protected $access = [
    	'*' => ['*'],
        'batchSign' => ['school_admin', 'teacher'],
        'getTpl' => ['school_admin', 'teacher'],
        'profile' => ['@'],
        'passwordReset' => ['@']
    ];

    protected $filter = [
        'index' => [],
        'batchSign' => [
        		'*' => [
        				'require' => ['group', 'overwrite'],
        		],
	            'school_admin' => [
	                	'default' => 'access_class',
	            ],
	            'teacher' => [
	                	'default' => 'access_class',
	            ],
        ],
        'pk' => [
            '*' => [
                'default' => ['base64' => 1]
            ],
        ],
        'isGuest' => [
            '*' => [
                'default' => ['school_alias' => null],
            ]
        ],
        'school' => [
            '*' => [
                'require' => ['school_alias'],
            ]
        ],
        'login' => [
            '*' => [
                'require' => ['school_alias', 'username', 'password', 'remember'],
            ]
        ],
        'adminLogin' => [
            '*' => [
                'require' => ['username', 'password', 'remember'],
            ]
        ],
        'passwordReset' => [
            '*' => [
                'require' => ['old_password', 'password']
            ]
        ],
    ];
    
    protected $behavior = '\behaviors\SiteBehavior';

    public function actionIndex()
    {
        return 'Home Page';
    }

    public function actionBatchSign()
    {
    	$data = $file = null;
    	
    	//是文件，读取数据并返回
        if (isset($_FILES['batch_sign']['tmp_name'])) {
        	$file = $_FILES['batch_sign']['tmp_name'];
        	if (!is_file($file)) {
        		$this->addError('file', self::ERR_VALID);
        		$this->addError('msg', '上传文件无效');
        		return $this->response(null, $this, 400);
        	}
        	
        	$model = new BatchSignModel();
        	if (!$model->setData($data, $file, $this->params['group'])) {
        		return $this->response(null, $model, 400);
        	}
        	return $this->response($model->getData());
        }
        
        //是数据，开始批量插入
        if (isset($this->params['data'])) {
        	$data = (array)$this->params['data'];
        	if (!$data) {
        		$this->addError('data', self::ERR_EMPTY);
	        	$this->addError('msg', '上传数据为空');
	        	return $this->response(null, $this, 400);
        	}
        } else {
        	$this->addError('data', self::ERR_EMPTY);
        	$this->addError('msg', '上传数据为空');
        	return $this->response(null, $this, 400);
        }
        
        $model = new BatchSignModel();
        
        if (!$model->setData($data, $file, $this->params['group'])) {
        	return $this->response(null, $model, 400);
        }
        
        $res = $model->batch_sign($this->params['school_id'], $this->params['class_id'], !!$this->params['overwrite']);
        
        return $this->response($res, $model, 201);
    }

    public function actionPk()
    {
        $res = [
            'pk' => file_get_contents($this->params['base64'] ? RSA_PB_KEY_BASE64 : RSA_PB_KEY),
            'nonce' => $this->security->generateRandomString(13),
            'ts' => time()
        ];
        $this->session->set('nonce', $res['nonce']);
        $this->session->set('ts', $res['ts']);

        return $this->response($res);
    }

    public function actionLogin()
    {
    	//check ip
    	if ($this->ipWall->notAllowedIp()) {
    		throw new HttpException(502);
    	}

        $model = new LoginModel();
        $res = $model->login($this->params['school_alias'], $this->params['username'], $this->params['password'], $this->params['remember']);
        if ($res) {
            $identity = $this->user->getIdentity();
            $profile = $identity->profile();
            $res = $profile;
        }
        
        return $this->response($res, $model, 200);
    }

    public function actionAdminLogin()
    {
        $model = new LoginModel();
        $res = $model->login(null, $this->params['username'], $this->params['password'], $this->params['remember']);
        if ($res) {
            $identity = $this->user->getIdentity();
            $profile = $identity->profile();
            $res = $profile;
        }
        return $this->response($res, $model, 200);
    }

    public function actionIsGuest()
    {
        if ($this->user->isGuest()) {
            return $this->attemptLogin();
        }

        $identity = $this->user->getIdentity();
        $profile = $identity->profile();
        if (@$profile['school_alias'] === $this->params['school_alias']) {
            return ['status' => 0, 'profile' => $profile];
        }

        $this->user->logout();
        return $this->attemptLogin();
    }

    protected function attemptLogin()
    {
        if (!in_array($this->params['school_alias'], ['zy'], true)) {
            return ['status' => 1];
        }

        $request = $this->request;
        $referer = $request->getReferrer();

        $prefix = 'http://mengoo.doctor-u.cn/clinic/login.html?token=';
        $prefixLen = strlen($prefix);

        if (strpos($referer, $prefix) === 0) {
            $token = urldecode(substr($referer, $prefixLen));
            return $this->ilabLogin($token);
        } else {
            return $this->tempLogin();
        }
    }

    /**
     * ['status' => xxx, ...] status指的是isGuest
     */
    protected function ilabLogin($token)
    {
        $ilabUserFromToken = IlabJwt::getBody($token);

        if (!$ilabUserFromToken) {
            return ['status' => 1];
        }

        $ilabUser = TempSchoolUserModel::getIlabUserByIlabId($ilabUserFromToken['id']);
        if ($ilabUser) {
            $loginModel = new LoginModel();
            $login = $loginModel->loginByUserId($ilabUser['user_id'], SchoolUserModel::GROUP_STUDENT, 1);
            $profile = $this->user->getIdentity()->profile();
            return $login ? ['status' => 0, 'profile' => $profile] : ['status' => 1];
        } else {
            return $this->tempLogin(
                $ilabUserFromToken['un'] . time() . $this->security->generateRandomString(3),
                'ilab_' . $ilabUserFromToken['dis'],
                TempSchoolUserModel::TEMP_ILAB,
                $ilabUserFromToken);
        }
    }

    /**
     * ['status' => xxx, ...] status指的是isGuest
     */
    protected function tempLogin($username = null, $name = null, $temp = TempSchoolUserModel::TEMP_RANDOM, $ilabUserFromToken = null)
    {
        $school = SchoolModel::getByAlias($this->params['school_alias']);
        if (!$school) {
            return ['status' => 1];
        }
        $res = TempUserModel::generateTempUser([
            'school_id' => $school[0]['id'],
            'username' => $username === null ? $this->params['school_alias'] . '_' . time() . $this->security->generateRandomString(3) : $username,
            'name' => $name === null ? $this->params['school_alias'] : $name,
            'password' => '123456',
        ], $temp, $ilabUserFromToken);

        if ($res) {
            $loginModel = new LoginModel();
            $login = $loginModel->loginByUserId($res['id'], SchoolUserModel::GROUP_STUDENT, 1);
            return $login ? ['status' => 0, 'profile' => $this->user->getIdentity()->profile()] : ['status' => 1];
        } else {
            return ['status' => 1];
        }
    }

    public function actionSchool()
    {
        $res = SchoolModel::getByAlias($this->params['school_alias']);
        return $this->response($res);
    }

    public function actionProfile()
    {
        $identity = $this->user->getIdentity();
        $profile = $identity->profile();
        return $this->response($profile, $identity, 200);
    }

    public function actionLogout()
    {
        if ($this->user->isGuest()) {
            return ['status' => 1];
        }

        $this->user->logout();
        return ['status' => $this->user->isGuest() ? 1 : 0];
    }

    public function actionGetTpl()
    {
        $index = isset($_GET['tpl']) ? (in_array($_GET['tpl'], [0 ,1]) ? $_GET['tpl'] : 1) : 1;

        $tpl = [
            	[DIR_STATIC .'/tpl/teacherTemplate.xlsx', '老师模板.xlsx'],
        		[DIR_STATIC .'/tpl/studentTemplate.xlsx', '学生模板.xlsx']
        ];

        $this->response->sendFile($tpl[$index][0], $tpl[$index][1]);
    }

    public function actionPasswordReset()
    {
        $identity = $this->user->getIdentity();
        if (!LoginModel::verify_password($this->params['old_password'], $identity['password'])) {
            $this->addError('old_password', self::ERR_VALID);
            return $this->response(null);
        }
        $userModel = new SchoolUserModel();
        $res = $userModel->_set(['id' => $identity['id'], 'password' => $this->params['password']]);
        return $this->response($res, $userModel, 204);
    }

    protected function access_class()
    {
        $identity = $this->user->getIdentity();
        $role = $this->user->getRole();

        $this->params['school_id'] = $identity['school_id'];

        if ($role === 'school_admin') {
            $this->params['class_id'] = null;
        } else {
            $this->params['class_id'] = $identity->getClassId();
            $this->params['group'] = SchoolUserModel::GROUP_STUDENT;
        }
    }
}
