<?php
namespace user\models;

use vendor\base\ValidateModel;

class SchoolModel extends ValidateModel
{
	const NAME = 'school';
	
	const STATUS_DELETED = 0;
	const STATUS_ACTIVE = 1;
	
	protected static $fields = [
			'id' => null,
			'name' => null,
			'alias' => null,
			'site_name' => '',
			'login_logo' => null,
			'logo' => null,
			'inside_logo' => null,
			'bg' => null,
			'banner' => null,
			'server' => null,
			'declaration_doc' => null,
			'declaration_video' => null,
			'show_declaration' => 0,
			'site_introduction' => '',
			'project_name' => null,
			'status' => self::STATUS_ACTIVE,
			'contact_person' => null,
			'contact_phone' => null,
			'contact_addr' => null,
			'contact_email' => null
	];
	
	protected static $sets = [
			'auto_inc' => '_id',
			'hash_id' => 'id',
			'id' => ['id'],
	];
	
	protected static $filters = [
			'before' => [
					'i' => ['status'],
					's' => ['name', 'alias', 'site_name', 'server', 'contact_person', 'contact_phone', 'contact_addr', 'contact_email'],
					'html' => ['site_introduction','project_name'],
					'img' => ['login_logo', 'logo', 'inside_logo', 'bg', 'banner'],
					'b' => ['show_declaration'],
			]
	];
	
	protected static $validates = [
			'require' => ['name', 'alias', 'bg', 'logo'],
			'repeat' => [['alias'], ['name']],
			'string' => [
					'name' => ['min' => 0, 'max' => 255, 'truncate' => false],
					'alias' => ['min' => 0, 'max' => 255, 'truncate' => false],
					'site_name' => ['min' => 0, 'max' => 255, 'truncate' => false],
					'server' => ['min' => 0, 'max' => 255, 'truncate' => false],
					'site_introduction' => ['min' => 0, 'max' => 600, 'truncate' => false],
					'project_name' => ['min' => 0, 'max' => 300, 'truncate' => false],
					'contact_person' => ['min' => 0, 'max' => 255, 'truncate' => false],
					'contact_phone' => ['min' => 0, 'max' => 255, 'truncate' => false],
					'contact_addr' => ['min' => 0, 'max' => 255, 'truncate' => false],
					'contact_email' => ['min' => 0, 'max' => 255, 'truncate' => false],
			]
	];
	
	protected static $constraints = [
			'id' => [
					['model' => SchoolUserModel::class, 'targets' => ['id' => 'school_id']],
					['model' => SchoolClassModel::class, 'targets' => ['id' => 'school_id']]
			],
	];
	
	protected static $searchFilter = ['name' => 'name', 'alias' => 'alias'];
	
	public function _list($search, $size = null, $page = 0)
	{
		$res = parent::general_list([], [], $search, [], $size, $page);
		return $res;	
	
	}
	
	public static function _get($id, $fields = null)
	{
		$fields ?: $fields = self::getFields();
		$res = self::_select(['id' => $id], $fields);
		return $res;
	}
	
	public static function getByAlias(string $alias, $fields = null)
	{
		$fields ?: $fields = self::getFields();
		$res = self::_select(['alias' => $alias], $fields);
		return $res;
	}
	
	public function _set($id, $vals = [])
	{
		$pack = ['id' => $id];
		return $this->internal_set($pack, $vals);
	}
	
}