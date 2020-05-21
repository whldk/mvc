<?php
namespace user\models;

use vendor\base\IdentityInterface;
use vendor\base\AppTrait;

class IdentityModel implements IdentityInterface, \ArrayAccess
{
	use AppTrait;
	
	const AUTHKEY_LEN = 32;
	
	const STATUS_DELETED = 0;
	const STATUS_ACTIVE = 1;
	
	const TYPE_ADMIN = 0;
	const TYPE_USER = 1;
	
	const COOKIE_LIFETIME = 2592000;
	
	const GROUP_ADMIN = 0;
	const GROUP_SCHOOL_ADMIN = 1;
	const GROUP_TEACHER = 2;
	const GROUP_STUDENT = 3;
	
	protected static $groups = [
			self::GROUP_ADMIN,
			self::GROUP_SCHOOL_ADMIN,
			self::GROUP_TEACHER,
			self::GROUP_STUDENT
	];
	
	protected static $roles = [
			self::GROUP_ADMIN => 'admin',
			self::GROUP_SCHOOL_ADMIN => 'school_admin',
			self::GROUP_TEACHER => 'teacher',
			self::GROUP_STUDENT => 'student'
	];
	
	protected static $models = [
			self::TYPE_ADMIN => [
					'model' => AdminModel::class,
					'fields' => [
							'id',
							'group',
							'username',
							'password',
							'authkey',
							'name',
					]
			],
			self::TYPE_USER => [
					'model' => UserModel::class,
					'fields' => [
							'id',
							'school_id',
							'group',
							'username',
							'password',
							'authkey',
							'name',
							'gender',
							'avatar',
					]
			]
	];
	
	protected static $instance;
	
	protected $identity;
	protected $profile;
	protected $role;
	protected $id;
	protected $authkey;
	protected $type;
	
	public static function getInstance()
	{
		return self::$instance;
	}
	
	protected function __construct($identity)
	{
		if (!$identity) {
			throw new \Exception('identity must be set');
		}
		
		$this->identity = $identity;
		
		$this->id = [$identity['id'], $identity['group']];
		
		$this->authkey = $identity['authkey'];
		
		$this->type = $identity['type'];
		
		unset($this->identity['authkey']);
	}
	
	public function profile()
	{
		if ($this->profile !== null) {
			return $this->profile;
		}
		
		if (!$this->identity) {
			return null;
		}
		
		$profile = $this->identity;
		unset($profile['authkey'], $profile['password']);
		
		switch ($this->identity['group']) {
			case self::GROUP_STUDENT :
				$class = StudentModel::hasOne($profile, IdentitySchoolClassModel::class, ['name AS class_name', 'id AS class_id']);
				$profile += $class ?: ['class_name' => '', 'class_id' => ''];
			case self::GROUP_SCHOOL_ADMIN :
			case self::GROUP_TEACHER :
				$school = SchoolModel::one($profile['school_id'], ['logo', 'inside_logo', 'alias AS school_alias', 'name AS school_name']);
				$profile += $school ?: [];
		}
		
		return $this->profile = $profile;
	}
	
	/**
	 * @param array $id ['xxxx', '1'], the first element is user_id, second one is group
	 * @return IdentityModel|null
	 */
	public static function findById($id)
	{
		list($user_id, $group) = $id;
		$user_id = (string)$user_id;
		$group = (int)$group;
		
		$type = self::getType($group);
		$model = self::$models[$type];
		/* @var \vendor\base\Model $modelClass */
		$modelClass = $model['model'];
		
		$identity = $modelClass::_select_one(
				['id' => $user_id, 'group' => $group, 'status' => self::STATUS_ACTIVE],
				$model['fields']
		);
		
		if ($identity) {
			$identity['type'] = $type;
			$identityClass = self::class;
			self::$instance = new $identityClass($identity);
		} else {
			self::$instance = null;
		}
		
		return self::$instance;
	}
	
	public static function findByName(string $school_id, string $username)
	{
		$username = trim($username);
		
		if ($school_id) {
			$type = self::TYPE_USER;
			$model = self::$models[$type];
			/* @var \vendor\base\Model $modelClass */
			$modelClass = $model['model'];
			$identity = $modelClass::_select_one(
					['school_id' => $school_id, 'username' => $username, 'status' => self::STATUS_ACTIVE],
					$model['fields']
			);
		} else {
			$type = self::TYPE_ADMIN;
			$model = self::$models[$type];
			/* @var \vendor\base\Model $modelClass */
			$modelClass = $model['model'];
			$identity = $modelClass::_select_one(
					['username' => $username, 'status' => self::STATUS_ACTIVE],
					$model['fields']
			);
		}
		
		if ($identity) {
			$identity['type'] = $type;
			$identityClass = self::class;
			self::$instance = new $identityClass($identity);
		} else {
			self::$instance = null;
		}
		
		return self::$instance;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * @tutorial 每次调用都会刷新
	 * {@inheritDoc}
	 * @see \vendor\base\IdentityInterface::getAuthkey()
	 */
	public function getAuthkey()
	{
		/* @var $security \vendor\base\security */
		$security = static::getSecurity();
		$this->authkey = $security->generateRandomString(self::AUTHKEY_LEN);
		
		$modelClass = self::$models[$this->type]['model'];
		$modelClass::_update(['id' => $this->identity['id']], ['authkey' => $this->authkey]);
		
		return $this->authkey;
	}
	
	public function validateAuthKey($authKey)
	{
		if ($this->authkey === $authKey) {
			return true;
		}
	}
	
	public function getRole()
	{
		if (!$this->role) {
			if (!$this->identity) {
				$this->role = '?';
			} else {
				$this->role = self::$roles[$this->identity['group']] ?? '?';
			}
		}
		return $this->role;
	}
	
	public function setExtraCookies()
	{
		if ($this->identity['group'] == self::GROUP_ADMIN) {
			return;
		}
		
		$profile = $this->profile();
		$cookies = $this->response->getCookies();
		$cookies->set([
				'name' => 'school_alias',
    			'expire' => time() + self::COOKIE_LIFETIME,
    			'value' => @$profile['school_alias'],
				'httpOnly' => false,
				'path' => '/',
		]);
		$cookies->set([
				'name' => 'username',
    			'expire' => time() + self::COOKIE_LIFETIME,
    			'value' => @$profile['username'],
				'httpOnly' => false,
				'path' => '/',
		]);
	}
	
	public function renewExtraCookies()
	{
		if ($this->identity['group'] == self::GROUP_ADMIN) {
			return;
		}
		
		$cookies = $this->request->getCookies();
		$school_alias = $cookies->get('school_alias');
		$username = $cookies->get('username');
		$school_alias['expire'] = $username['expire'] = time() + self::COOKIE_LIFETIME;
		$school_alias['httpOnly'] = $username['httpOnly'] = false;
		
		$cookies = $this->response->getCookies();
		$cookies->set($school_alias);
		$cookies->set($username);
	}
	
	public function delExtraCookies()
	{
		if ($this->identity['group'] == self::GROUP_ADMIN) {
			return;
		}
	
		$cookies = $this->response->getCookies();
		$cookies->del('school_alias');
		$cookies->del('username');
	}
	
	public function getClassId()
	{
		if (!$this->identity) {
			return [];
		}
		
		if (!key_exists('class_id', $this->identity)) {
			$this->identity['class_id'] = null;
			if ($this->identity['group'] == self::GROUP_TEACHER) {
				$class_ids = IdentityTeacherClassModel::getClassIds($this->identity['school_id'], $this->identity['id']);
				$this->identity['class_id'] = $class_ids ?: [];
			} elseif ($this->identity['group'] == self::GROUP_STUDENT) {
				$class_ids = IdentityStudentClassModel::getClassIds($this->identity['school_id'], $this->identity['id']);
				$this->identity['class_id'] = $class_ids ?: [];
				$this->identity['main_class'] = $class_ids ? $class_ids[0] : '';
			}
		}
	
		return $this->identity['class_id'];
	}
	
	public function getMainClass()
	{
		if (!$this->identity) {
			return null;
		}
		if (!key_exists('main_class', $this->identity)) {
			$this->identity['main_class'] = null;
			$this->getClassId();
		}
		return $this->identity['main_class'];
	}
	
	public function getGrade($class_id = null)
	{
		if (!$this->identity) {
			return $class_id === null ? [] : null;
		}
		
		if (!key_exists('grade', $this->identity)) {
			$this->identity['grade'] = [];
			if ($this->identity['group'] == self::GROUP_STUDENT) {
				$class_ids = $this->getClassId();
				if ($class_ids) {
					$classes = SchoolClassModel::_select(['id' => $class_ids], ['grade', 'id']);
					$this->identity['grade'] = array_column($classes, 'grade', 'id');
				}
			}
		}
		
		if ($class_id === null) {
			return $this->identity['grade'];
		} else {
			return $this->identity['grade'][$class_id] ?? null;
		}
	}
	
	public function canAccessUsers($user_ids, $group = null)
	{
		$user_ids = array_values(array_unique((array)$user_ids));
		if (!$user_ids) {
			return false;
		}
		
		$role = $this->getRole();
		switch ($role) {
			case 'admin' : 
				return true;
			case 'school_admin' :
				$res = SchoolUserModel::_select([
						'school_id' => $this->identity['school_id'],
						'id' => $user_ids
				], 'id');
				return count($res) === count($user_ids);
			case 'teacher' : 
				$res = StudentModel::_select([
						'school_id' => $this->identity['school_id'],
						'id' => $user_ids
				], 'id');
				if (count($res) !== count($user_ids)) {
					return false;
				}
				$res = StudentClassModel::_select([
						'user_id' => $user_ids
				], 'class_id');
				$res = array_unique(array_column($res, 'class_id'));
				return array_diff($res, $this->getClassId()) === [];
			case 'student' : 
				return count($user_ids) === 1 && $this->identity['id'] = $user_ids[0];
		}
		
		return false;
	}
	
	public function canAccessClasses($class_ids)
	{
		$class_ids = array_values(array_unique((array)$class_ids));
		
		$role = $this->getRole();
		switch ($role) {
			case 'admin' :
				return true;
			case 'school_admin' :
				$res = SchoolClassModel::_select([
						'school_id' => $this->identity['school_id'],
						'id' => $class_ids
				], 'id');
				return count($res) === count($class_ids);
			case 'teacher' :
				return array_diff($class_ids, $this->getClassId()) === [];
			case 'student' :
				return array_diff($class_ids, $this->getClassId()) === [];
		}
		
		return false;
	}	
	
	protected static function getType($group)
	{
		return $group == self::GROUP_ADMIN ? self::TYPE_ADMIN : self::TYPE_USER;
	}
	
	public function offsetExists($offset) {
		return isset($this->identity[$offset]);
	}
	
	public function offsetGet($offset) {
		return isset($this->identity[$offset]) ? $this->identity[$offset] : null;
	}
	
	public function offsetSet($offset, $value) {}
	
	public function offsetUnset($offset) {}
	
	public function errors() {}
}