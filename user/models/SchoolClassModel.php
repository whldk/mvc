<?php
namespace user\models;

use vendor\base\ValidateModel;
use course\models\CourseAccessModel;
use exam\models\ExamAccessModel;

class SchoolClassModel extends ValidateModel
{
	const NAME = 'school_class';
	
	const STATUS_ACTIVE = 1;
	const STATUS_DELETED = 0;
	
	protected static $sets = [
			'auto_inc' => '_id',
			'hash_id' => 'id',
			'id' => ['id'],
	];
	
	protected static $fields = [
			'id' => '',
			'school_id' => '',
			'name' => '',
			'grade' => 0,
			'major' => '',
			'status' => self::STATUS_ACTIVE,
			'created_at' => null,
			'updated_at' => null,
	];
	
	protected static $filters = [
			'before' => [
					'i' => ['grade', 'status'],
					's' => ['school_id', 'name', 'major'],
					'ts' => ['ct' => 'created_at', 'mt' => 'updated_at'],
			]
	];

	protected static $constraints = [
			'id' => [
					['model' => StudentClassModel::class, 'targets' => ['id' => 'class_id']],
					['model' => TeacherClassModel::class, 'targets' => ['id' => 'class_id']]
			],
	];
	
	protected static $cascades = [
			'id' => [
					['model' => CourseAccessModel::class, 'targets' => ['id' => 'class_id']],
					['model' => ExamAccessModel::class, 'targets' => ['id' => 'class_id']],
					['model' => TeacherClassModel::class, 'targets' => ['id' => 'class_id']],
			]
	];
	
	protected static $validates = [];
	
	public static function validates()
	{
		if (!self::$validates) {
			self::$validates = [
				'require' => ['name'],
				'repeat' => ['name'],
				'exist' => [
						'school_id' => ['model' => SchoolModel::class, 'targets' => ['school_id' => 'id']],
				],
				'range' => [
						'status' => [self::STATUS_ACTIVE, self::STATUS_DELETED],
				],
				'string' => [
						'name' => ['min' => 0, 'max' => 255, 'truncate' => false],
						'major' => ['min' => 0, 'max' => 255, 'truncate' => false],
				],
				'number' => [
						'grade' => ['min' => date('Y') - 10, 'max' => date('Y')]
				]
			];
			self::orderValidates(self::$validates);
		}
		return self::$validates;
	}
	
	public static function view()
	{
		/* @var IdentityModel $identity */
		$user = static::getUser();
		$identity = $user->getIdentity();
		if ($user->getRole() === 'school_admin') {
			return [
					'school_id' => $identity['school_id'],
			];
		} else {
			return [
					'school_id' => $identity['school_id'],
					'id' => $identity->getClassId()
			];
		}
	}
	
	protected static $searchFilter = ['class_name' => self::NAME . '.name'];
	protected static $priorOrder = [[self::NAME .'._id', 'desc']];
	protected static $matchFilter = ['grade', 'status'];
	protected static $hasMulti = [
			TeacherClassModel::class => ['key' => 'id', 'rel_key' => 'class_id'],
			TeacherModel::class => ['middle' => TeacherClassModel::class, 'key' => 'id', 'rel_key' => 'class_id', 'attach' => 'teachers'],
	];
	
	/**
	 * @tutorial 正常版list
	 */
	public static function _list($grade, $status, $search, $size, $page, $attachTeachers = true)
	{
		$matches = compact(self::$matchFilter);
		$res = parent::general_list($matches, [], $search, [], $size, $page);
		if ($res['_list'] && $attachTeachers) {
			$res['_list'] = self::hasMultis(
					$res['_list'],
					TeacherModel::class, 
					['username', 'name as teacher_name', 'id as user_id'],
					true,
					false
				);
		}
		return $res;
	}
	
	/**
	 * @tutorial 简单版list
	 */
	public static function options($search, $size, $page)
	{
		$res = self::_list(null, null, $search, $size, $page, false);
		return $res;
	}
	
	public static function _get($id, $fields = null)
	{
		$fields ?: $fields = self::getFields();
		$res = self::_select(['id' => $id], $fields);
		return $res;
	}
	
	public function _set($vals)
	{
		return $this->set_one($vals);
	}
	
	public function deactivate($school_id, $grade)
	{
		$res = $this->callInTransaction(function () use ($school_id, $grade) {
			$res = ['class_num' => 0, 'user_num' => 0];
			$res['class_num'] = self::_update(['school_id' => $school_id, 'grade' => $grade], ['status' => self::STATUS_DELETED]);
			$class_ids = self::_select_col(['school_id' => $school_id, 'grade' => $grade, 'status' => self::STATUS_DELETED], 'id');
			if ($class_ids) {
				$res['user_num'] = StudentModel::deative($school_id, $class_ids);
			}
			return $res;
		});
		return $res;
	}
	
}