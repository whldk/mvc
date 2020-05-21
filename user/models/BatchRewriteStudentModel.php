<?php
namespace user\models;

class BatchRewriteStudentModel extends BatchStudentModel
{
	protected static $sets = [
			'auto_inc' => '_id',
			'hash_id' => 'id',
			'id' => ['school_id', 'username', 'group'],
	];
}