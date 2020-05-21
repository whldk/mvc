<?php
namespace user\models;

use vendor\base\ValidateModel;

class AdminModel extends ValidateModel
{
	const NAME = 'admin';
	
	const STATUS_DELETED = 0;
	const STATUS_ACTIVE = 1;
	
	const GROUP_ADMIN = 0;
	
	const statuses = [self::STATUS_ACTIVE, self::STATUS_DELETED];
	
	protected static $sets = [
			'auto_inc' => '_id',
			'hash_id' => 'id',
			'id' => ['id'],
	];
	
	protected static $fields = [
			'id' => null,
			'group' => self::GROUP_ADMIN,
			'username' => null,
			'password' => null,
			'name' => null,
			'status' => self::STATUS_ACTIVE,
			'created_at' => null,
			'updated_at' => null
	];
	
	protected static $extraFields = [
			'authkey' => null,
	];
	
	protected static $sensitiveFields = [
			'password',
			'authkey',
	];
	
	protected static $filters = [
			'before' => [
					's' => ['username', 'real_name'],
					'ts' =>  ['ct' => 'created_at', 'mt' => 'updated_at'],
					'map' => [
							'password' => [
									'callback' => [self::class, 'openssl_private_decrypt']
							]
					],
					'ignore' => ['group'],
			],
			'after' => [
					'map' => [
							'password' => [
									'callback' => [self::class, 'generate_password_hash']
							],
					],
			]
	];
	
	protected static $validates = [
			'require' => ['username', 'password', 'name'],
			'readonly' => ['username'],
			'regular' => [
					'username' => '/^[a-zA-Z0-9_]+$/',
					'password' => '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~\-:\.@()\[\]";,]+$/'
			],
			'string' => [
					'username' => ['min' => 2, 'max' => 255, 'truncate' => false],
					'password' => ['min' => 6, 'max' => 255, 'truncate' => false],
					'name' => ['min' => 1, 'max' => 255, 'truncate' => false]
			],
			'repeat' => ['username'],
			'range' => [
					'status' => self::statuses,
			]
	];
	
	protected static $searchFilter = ['username' => 'username', 'name' => 'name'];
	protected static $priorOrder = [[self::NAME . '._id', 'desc']];
	protected static $matchFilter = ['status'];
	
	public static function listFields()
	{
		$fields = array_keys(static::$fields);
		return array_diff($fields, self::$sensitiveFields);
	}
	
	public static function getFields()
	{
		$fields = array_keys(static::$fields);
		return array_diff($fields, self::$sensitiveFields);
	}
	
	public static function _list($status = self::STATUS_ACTIVE, $search = [], $size = self::PAGESIZE, $page = 0)
	{
		$matches = compact(self::$matchFilter);
		$res = self::general_list($matches, [], $search, [], $size, $page);
		return $res;
	}
	
	public static function _get($id, $fields = null, $noSensitiveFields = true)
	{
		$fields ?: $fields = self::getFields();
		if ($noSensitiveFields == false) {
			$fields = array_merge($fields, self::$sensitiveFields);
		}
		$res = self::_select(['id' => $id], $fields);
		return $res;
	}
	
	public function _set($vals)
	{
		$res = $this->set_one($vals);
		return $res;
	}
	
	public function deactive($ids)
	{
		$res = self::_update(['id' => $ids], ['status' => self::STATUS_DELETED]);
		return $res;
	}
	
	public static function generate_password_hash($password)
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
	public static function openssl_private_decrypt($password)
	{
		$prKey = file_get_contents(RSA_PR_KEY);
		//ignore the openssl_private_decrypt result, when result is false, $password will be the same, it makes sense here
		if (!openssl_private_decrypt(base64_decode(str_replace(' ', '+', $password)), $password, $prKey)) {
			return false;
		}
		
		$password = json_decode($password, true);
		if (count($password) === 3 && isset($password[0], $password[1], $password[2])) {
			$window = abs(time() - $password[0]);
			if ($window > 120) {
				return false;
			}
				
			$session = static::getSession();
			$ts = $session->get('ts');
			$nonce = $session->get('nonce');
			if ($ts != $password[0] || $nonce != $password[1]) {
				return false;
			}
			$password = $password[2];
			return $password;
		}
		
		return false;
	}
}