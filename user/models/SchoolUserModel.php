<?php
namespace user\models;

class SchoolUserModel extends UserModel
{
	public static function view()
	{
		$user = static::getUser();
		$identity = $user->getIdentity();
		if ($user->getRole() !== 'admin' && $user->getRole() !== '?') {
			return [
					'school_id' => $identity['school_id']
			];
		}
		return [];
	}
}