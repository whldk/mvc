<?php
namespace user\models;

class TeacherModel extends SchoolUserModel
{
	protected static $fields = [
			'id' => null,
			'school_id' => '',
			'group' => self::GROUP_TEACHER,
			'username' => null,
			'iusername' => null,
			'password' => null,
			'name' => null,
			'gender' => self::G_UNKNOWN,
			'avatar' => '',
			'intro' => '',
			'intro_page' => '',
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
					['model' => TeacherClassModel::class, 'targets' => ['id' => 'user_id']]
			]
	];
	
	protected static $hasMulti = [
			SchoolClassModel::class => ['middle' => TeacherClassModel::class, 'key' => 'id', 'rel_key' => 'user_id'],
			TeacherClassModel::class => ['key' => 'id', 'rel_key' => 'user_id'],
	];
	
	public static function view()
	{
		$identity = static::getUser()->getIdentity();
		return [
				'group' => self::GROUP_TEACHER,
				'school_id' => $identity['school_id']
		];
	}
	
	public static function filterTeachers($teacher_ids)
	{
		$res = self::_select([
				'id' => $teacher_ids,
				'group' => SchoolUserModel::GROUP_TEACHER,
				'status' => SchoolUserModel::STATUS_ACTIVE
		], ['id']);
		
		return $res ? array_column($res, 'id') : [];
	}
	
}