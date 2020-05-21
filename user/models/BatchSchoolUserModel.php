<?php
namespace user\models;

class BatchSchoolUserModel extends SchoolUserModel
{
	protected static $filters = [
			'before' => [
					's' => ['school_id', 'username', 'real_name'],
					'i' => ['group', 'gender'],
					'ts' =>  ['ct' => 'created_at', 'mt' => 'updated_at'],
					'ignore' => ['avatar'],
			],
			'after' => [
					'map' => [
							'password' => [
									'callback' => ['user\models\SchoolUserModel', 'generate_password_hash']
							],
							'iusername' => [
									'callback' => ['user\models\SchoolUserModel', 'generate_iusername'],
									'args' => ['username'],
							],
					],
			]
	];
	
	protected static $validates = [
			'require' => ['school_id', 'group', 'username', 'password', 'name'],
			'readonly' => ['password'],
			'range' => [
					'status' => self::statuses,
					'group' => self::groups,
					'gender' => [self::G_FEMALE, self::G_MALE, self::G_UNKNOWN],
			],
			'regular' => [
					'username' => '/^[a-zA-Z0-9_]+$/',
					'password' => '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~\-:\.@()\[\]";,]+$/'
			],
			'string' => [
					'username' => ['min' => 2, 'max' => 255, 'truncate' => false],
					'password' => ['min' => 6, 'max' => 255, 'truncate' => false],
					'name' => ['min' => 1, 'max' => 255, 'truncate' => false]
			],
			'exist' => [
					'school_id' => ['model' => SchoolModel::class, 'targets' => ['school_id' => 'id']],
			],
			'repeat' => ['username'],
	];
	
	protected function internal_update($primaryKeyVals, $snapshot, $vals)
	{
		$res = parent::internal_update($primaryKeyVals, $snapshot, $vals);
		//主key主要是会根据是单个还是批量的情况变化，更新的时候批量上下文需返回id
		return $res !== null ? ['id' => $snapshot['id']] : $res;
	}
}