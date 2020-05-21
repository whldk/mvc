<?php
namespace user\models;

class IdentityStudentClassModel extends StudentClassModel
{
	protected static $hasOne = [
			IdentitySchoolClassModel::class => ['key' => 'class_id', 'rel_key' => 'id'],
	];
	
	public static function view()
	{
		/* @var IdentityModel $identity */
		$identity = static::getUser()->getIdentity();
		return [
				'school_id' => $identity ? $identity['school_id'] : null,
		];
	}
}