<?php
namespace user\models;

use vendor\exceptions\UnknownException;
use vendor\exceptions\UserErrorException;

class StudentModel extends SchoolUserModel
{
	protected static $fields = [
			'id' => null,
			'school_id' => '',
			'group' => self::GROUP_STUDENT,
			'username' => null,
			'iusername' => null,
			'password' => null,
			'name' => null,
			'gender' => self::G_UNKNOWN,
			'avatar' => '',
			'status' => self::STATUS_ACTIVE,
			'created_at' => null,
			'updated_at' => null
	];
	
	protected static $validates = [];
	
	public static function validates()
	{
		if (!self::$validates) {
			self::$validates = parent::$validates;
			self::$validates['require'] = ['username', 'password', 'name'];
		}
		return self::$validates;
	}
	
	protected static $cascades = [
			'id' => [
					['model' => StudentClassModel::class, 'targets' => ['id' => 'user_id']],
			]
	];
	
	protected static $hasOne = [
			IdentitySchoolClassModel::class => ['middle' => IdentityStudentClassModel::class],
			IdentityStudentClassModel::class => ['key' => 'id', 'rel_key' => 'user_id', 'view' => ['is_main' => 1]],
	];
	
	protected static $hasMulti = [
			SchoolClassModel::class => [
					'middle' => StudentClassModel::class,
					'key' => 'id',
					'rel_key' => 'user_id',
			],
			StudentClassModel::class => [
					'key' => 'id',
					'rel_key' => 'user_id',
					'fields' => ['is_main'],
			]
	];
	
	public static function view()
	{
		$identity = static::getUser()->getIdentity();
		return [
				'group' => self::GROUP_STUDENT,
				'school_id' => $identity['school_id']
		];
	}
	
	protected static $join = [
			'middle' => [
					'model' => StudentClassModel::class,
					'left_on' => [
							'id' => 'user_id'
					],
					'right_on' => [
							'class_id' => 'id'
					]
			],
			'model' => SchoolClassModel::class,
			'alias' => 'class', // 非必填
	// 		'fields' => [],	//非必填
	];
	
	protected static $matchFilter = ['class_id' => 'class_id', 'status' => self::NAME . '.status', 'group' => 'group'];
	
	public static function student_list($class_id, $status = self::STATUS_ACTIVE, $search = [], $size = self::PAGESIZE, $page = 0)
	{
		$matches = compact(array_keys(self::$matchFilter));
		$res = parent::general_list($matches, [], $search, [], $size, $page);
		return $res;
	}
	
	public function _set($vals)
	{
		$res = $this->callInTransaction(function () use ($vals) {
			$thisRes = parent::_set($vals);
			if ($thisRes === null) {
				throw new UnknownException();
			}
			if (isset($thisRes['id'])) {
				if (!isset($vals['class_id'])) {
					throw new UserErrorException(['class_id' => self::ERR_EMPTY]);
				}
				$classModel = new StudentClassModel();
				$classRes = $classModel->_set($thisRes['id'], $vals['class_id'], ['is_main' => 1]);
				if ($classRes === null) {
					throw new UnknownException();
				}
			}
			return $thisRes;
		});
		
		return $res;
	}
	
	public static function filterStudents($student_ids)
	{
		$res = self::_select([
				'id' => $student_ids,
				'group' => SchoolUserModel::GROUP_STUDENT,
				'status' => SchoolUserModel::STATUS_ACTIVE
		], ['id']);
		
		return $res ? array_column($res, 'id') : [];
	}
	
	/**
	 * @see SchoolClassModel::deactivate($school_id, $grade)
	 * @param string $school_id
	 * @param array $class_ids
	 * @return 
	 */
	public static function deative($school_id, $class_ids)
	{
		$db = static::getDb();
		$res = $db->update(self::NAME)->set(['status' => SchoolUserModel::STATUS_DELETED])
		->join(StudentClassModel::NAME, ['id' => 'user_id'])
		->where([StudentClassModel::NAME . '.school_id' => $school_id, StudentClassModel::NAME . '.class_id' => $class_ids])
		->result();
		if ($res === null) {
			self::throwDbException();
		}
		return $res;
	}
}