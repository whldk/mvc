<?php
namespace app\models;

use vendor\base\ValidateModel;
use user\models\SchoolUserModel;

class AppModel extends ValidateModel
{
	const NAME = 'app';
	
	const STATUS_DELETED = 0;
	const STATUS_ACTIVE = 1;
	const STATUS_PENDDING = 2;
	
	const statuses = [self::STATUS_ACTIVE, self::STATUS_DELETED, self::STATUS_PENDDING];
	
	protected static $sets = [
			'auto_inc' => '_id',
			'hash_id' => 'id',
			'id' => ['id'],
	];
	
	protected static $fields = [
			'id' => null,
			'type' => null,
			'name' => null,
			'info' => null,
			'site_url' => null,
			'callback_url' => null,
			'logo100x100' => null,
			'logo64x64' => null,
			'status' => self::STATUS_PENDDING,
			'approver_id' => null,
			'created_at' => null,
			'updated_at' => null
	];
	
	protected static $extraFields = [
			'app_key' => null,
			'app_secret' => null,
	];
	
	protected static $constraints = [
				'id' => [
						['model' => AppTokenModel::class, 'targets' => ['id' => 'app_id']],
			],
	];
	
	protected static $filters = [
			'before' => [
					'i' => ['status'],
					's' => ['type', 'name', 'info', 'site_url', 'callback_url'],
					'ts' =>  ['ct' => 'created_at', 'mt' => 'updated_at'],
					'img' => ['logo100x100', 'logo64x64']
			],
			'after' => [
					
			]
	];
	
	protected static $validates = [
			'require' => ['type', 'name', 'info', 'site_url'],
			'readonly' => ['name'],
			'url' => [
					'site_url',
					'callback_url',
			],
			'string' => [
					'type' => ['min' => 2, 'max' => 100, 'truncate' => false],
					'name' => ['min' => 3, 'max' => 255, 'truncate' => false],
					'info' => ['min' => 10, 'max' => 255, 'truncate' => true]
			],
			'range' => [
					'status' => self::statuses,
			],
			'exist' => [
					'approver_id' => [
							'model' => SchoolUserModel::class,
							'targets' => ['approver_id' => 'id']
					]
			],
			'repeat' => [
					['app_key'], ['app_secret']
			],
			'filter' => [
					'app_key' => [
							'callback' => [self::class, 'generateAppKeys'],
							'args' => ['id'],
							'results' => ['app_secret']
					],
			],
	];
	
	public static function validates()
	{
		if (!self::$validates) {
			self::$validates = [
					
			];
			self::orderValidates(self::$validates);
		}
		return self::$validates;
	}
	
	public function _list($status = null, $search = [], $size = null, $page = 0)
	{
		$res = [];
		/* @var $db \vendor\db\Db */
		$db = static::getDb();
		
		$this->search($search, ['type' => 'type', 'name' => 'name']);
		$query = $db->select(self::listFields())
			->from(self::NAME)
			->and_filter_where(['status' => $status])
			->and_filter_where($search);
		$offset = static::page($query, $page, $size, $res);
		if (!isset($res['_list'])) {
			$query->limit($offset, $size);
			$res['_list'] = $query->result();
		}
		
		return $res;
	}
	
	public function _get($id)
	{
		$res = [];
		/* @var $db \vendor\db\Db */
		$db = static::getDb();
		
		$res = $db->select(self::getFields())
                    ->from(self::NAME)
					->where(['id' => $id])
					->result();
		return $res;
	}
	
	public static function getByAppKey($appKey, $withAppKeys = false)
	{
		$fields = $withAppKeys === true ? array_merge(self::getFields(), ['app_key', 'app_secret']) : self::getFields();
		$res = static::getDb()->select($fields)
            ->from(self::NAME)
			->where(['app_key' => $appKey])
			->result();
		
		return $res ? $res[0] : [];
	}
	
	public function _set($id, $vals = [])
	{
		unset($vals['status']);
		$res = $this->internal_set(['id' => $id], $vals);
		return $res;
	}
	
	public function approve($id)
	{
		$fields = self::$fields;
		self::$fields += self::$extraFields;

        $vals = [
            'status' => self::STATUS_ACTIVE,
            'app_key' => '',
            'app_secret' => ''
        ];
        $res = $this->internal_set(['id' => $id], $vals);
		self::$fields = $fields;

		return $res ? self::getKeysFromCache($id) : null;
	}

	public function deactivate($id)
	{
        $vals = ['status' => self::STATUS_DELETED];
		$res = $this->internal_set(['id' => $id], $vals);
		return $res;
	}
	
	protected static $keyCache = [];
	
	/**
	 * 
	 * @param string $id
	 * @param array $keys ['app_key' => '', 'app_secret' => ']
	 */
	protected static function setKeysInCache($id, $keys)
	{
		self::$keyCache[$id] = $keys;
		return true;
	}
	
	protected static function getKeysFromCache($id)
	{
		return isset(self::$keyCache[$id]) ? self::$keyCache[$id] : null;
	}
	
	protected static function generateAppKeys($id)
	{
		$appKey = md5($id . time() . openssl_random_pseudo_bytes(8), false);
		$appSecret = hash_hmac('sha256', $appKey , openssl_random_pseudo_bytes(16), false);
		
		self::setKeysInCache($id, ['app_key' => $appKey, 'app_secret' => $appSecret]);
		
		return [$appSecret, $appKey];
	}
	
}