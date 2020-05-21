<?php
namespace user\models;

class BatchSchoolClassModel extends SchoolClassModel
{
	protected static $sets = [
			'auto_inc' => '_id',
			'hash_id' => 'id',
			'id' => ['name'],
	];
	
	protected function after_update($res, $primaryKeyVals, $snapshot, $vals)
	{
		return $primaryKeyVals + ['id' => $snapshot['id']];
	}
}