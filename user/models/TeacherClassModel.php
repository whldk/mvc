<?php
namespace user\models;

use vendor\base\ValidateModel;

class TeacherClassModel extends ValidateModel
{
	const NAME = 'teacher_class';
	
	protected static $sets = [
			'id' => ['class_id', 'user_id']
	];
	
	protected static $fields = [
		'school_id' => '',
		'user_id' => null,
		'class_id' => null,
		'created_at' => null
	];
	
	protected static $filters = [
			'before' => [
					's' => ['school_id', 'user_id', 'class_id'],
					'ts' =>  ['ct' => 'created_at'],
			]
	];
	
	protected static $validates = [
			'exist' => [
					'user_id' => [
							'model' => TeacherModel::class,
							'targets' => ['user_id' => 'id'],
					],
					'class_id' => [
							'model' => SchoolClassModel::class,
							'targets' => ['class_id' => 'id']
					]
			]
	];
	
	protected static $hasOne = [
			TeacherModel::class => ['key' => 'user_id', 'rel_key' => 'id'],
			TeacherClassModel::class => ['key' => 'class_id', 'rel_key' => 'id'],
	];
	
	public static function view()
	{
		/* @var IdentityModel $identity */
		$user = static::getUser();
		$identity = $user->getIdentity();
		if ($user->getRole() === 'school_admin') {
			return [
					'school_id' => $identity['school_id']
			];
		} else {
			return [
					'school_id' => $identity['school_id'],
					'user_id' => $identity['id']
			];
		}
	}
	
	public function _set($class_id, $user_id)
	{
		return $this->set_one(['class_id' => $class_id, 'user_id' => $user_id]);
	}
	
	public static function getClassIds($school_id, $user_id)
	{
		$res = self::_select_col(['school_id' => $school_id, 'user_id' => $user_id], 'class_id');
		return $res;
	} 
}