<?php
namespace user\models;

class BatchStudentModel extends StudentModel
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
	
	protected function internal_update($primaryKeyVals, $snapshot, $vals)
	{
		$res = parent::internal_update($primaryKeyVals, $snapshot, $vals);
		return $res !== null ? ['id' => $snapshot['id']] : $res;
	}
}