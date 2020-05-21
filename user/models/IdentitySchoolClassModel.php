<?php
namespace user\models;

class IdentitySchoolClassModel extends SchoolClassModel
{
	public static function view()
	{
		/* @var IdentityModel $identity */
		$identity = static::getUser()->getIdentity();
		return [
				'school_id' => $identity ? $identity['school_id'] : null,
		];
	}
}