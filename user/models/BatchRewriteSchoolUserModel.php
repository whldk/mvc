<?php
namespace user\models;

class BatchRewriteSchoolUserModel extends BatchSchoolUserModel
{
	protected static $sets = [
			'auto_inc' => '_id',
			'hash_id' => 'id',
			'id' => ['school_id', 'username', 'group'],
	];
}