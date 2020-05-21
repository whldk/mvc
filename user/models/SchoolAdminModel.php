<?php
namespace user\models;

class SchoolAdminModel extends SchoolUserModel
{
	protected static $fields = [
			'id' => null,
			'school_id' => '',
			'group' => self::GROUP_SCHOOL_ADMIN,
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
	
	public static function view()
	{
		$identity = static::getUser()->getIdentity();
		return [
				'group' => self::GROUP_SCHOOL_ADMIN,
				'school_id' => $identity['school_id']
		];
	}
	
}