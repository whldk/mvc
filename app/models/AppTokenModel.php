<?php
namespace app\models;

use vendor\base\ValidateModel;

class AppTokenModel extends ValidateModel
{
	const NAME = 'app_token';
	
	const ACCESS_TOKEN_EXPIRED_IN = 7776000;
	const AUTH_CODE_EXPIRED_IN = 600;
	
	const GROUP_SCHOOL_ADMIN = 1;
	const GROUP_TEACHER = 2;
	const GROUP_STUDENT = 3;
	
	const ERR_CODE_EXPIRED = 101;
	
	protected static $groups = [
			self::GROUP_SCHOOL_ADMIN,
			self::GROUP_TEACHER,
			self::GROUP_STUDENT
	];
	
	protected static $sets = [
			'id' => ['app_id', 'owner_id'],
	];
	
	protected static $fields = [
			'app_id' => null,
			'owner_id' => null,
			'group' => null,
			'access_token' => null,
			'refresh_token' => null,
			'expired_at' => null,
			'created_at' => null,
			'updated_at' => null	
	];
	
	protected static $filters = [
			'before' => [
					'i' => ['group'],
					'ts' =>  ['ct' => 'created_at', 'mt' => 'updated_at']
			]
	];
	
	protected static $tokenCache = [];
	
	protected static $validates = [];
	
	public static function validates()
	{
		if (!self::$validates) {
			self::$validates = [
				'exist' => [
						'app_id' => [
								'model' => AppModel::class,
								'targets' => ['app_id' => 'id'],
						],
// 						'owner_id' => [	//个人登录时授权code，无需验证
// 								'table' => TBL_USER,
// 								'targets' => ['owner_id' => 'id'],
// 						]
				],
				'range' => [
						'group' => self::$groups
				],
				'filter' => [
						'access_token' => [
								'callback' => [self::class, 'generateToken'],
								'args' => ['app_id', 'owner_id', 'access_token', 'refresh_token', 'expired_at'],
								'results' => ['expired_at', 'refresh_token']
						],
				],
				'repeat' => [
					['app_id', 'refresh_token'],
				]
			];
			self::orderValidates(self::$validates);
		}
		return self::$validates;
	}
	
	public function _get($app_id, $owner_id)
	{
		$res = [];
		/* @var $db \vendor\db\Db */
		$db = static::getDb();
		
		$res = $db->select(self::getFields())
			->from(self::NAME)
			->where(['app_id' => $app_id, 'owner_id' => $owner_id])
			->result();
			
		return $res;
	}
	
	public function _set($app_id, $owner_id, $vals = [])
	{
		$pack = compact(self::$sets['id']);
		$vals = [
				'access_token' => '',
				'refresh_token' => ''
			] + $vals;
		
		$res = $this->internal_set($pack, $vals);
		
		return $res ? self::getTokenFromCache($app_id, $owner_id) : null;
	}
	
	public static function getByAccessToken($app_id, $access_token)
	{
		$res = static::getDb()->select(self::getFields())
			->from(self::NAME)
			->where(['app_id' => $app_id, 'access_token' => $access_token])
			->result();
		
		return $res ? $res[0] : [];
	}
	
	public function refresh($app_id, $refresh_token)
	{
		$sets = self::$sets;
		
		self::$sets = ['id' => ['app_id', 'refresh_token']];
		$pack = compact(self::$sets['id']);
		$vals = ['access_token' => ''];
		$res = $this->internal_set($pack, $vals);
		
		self::$sets = $sets;
		
		return $res ? self::getTokenFromCache($app_id, $refresh_token) : null;
	}
	
	protected function get_exec($pack, $vals)
	{
		list($exec, $fields) = parent::get_exec($pack, $vals);
		if (key_exists('refresh_token', $pack) === true && $exec !== 'update') {
			return $this->addError('refresh_token', self::ERR_VALID);
		}
		return [$exec, $fields];
	}
	
	/**
	 * @param string $key1 app_id
	 * @param string $key2 owner_id or refresh_token
	 * @param array ['access_token', 'expired_in', 'refresh_token']
	 */
	public static function setTokenInCache($key1, $key2, $tokenInfo)
	{
		self::$tokenCache[$key1 . $key2] = $tokenInfo;
		return true;
	}
	
	/**
	 * @param string $key1 app_id
	 * @param string $key2 owner_id or refresh_token
	 */
	public static function getTokenFromCache($key1, $key2)
	{
		return isset(self::$tokenCache[$key1 . $key2]) ? self::$tokenCache[$key1 . $key2] : null;
	}
	
	protected static function generateToken($app_id, $owner_id, $access_token, $refresh_token, $expired_at)
	{
		$now = time();
		$data = $app_id . $owner_id . $now;
		
		if (in_array('refresh_token', self::$sets['id']) === true) {
			//用code或refresh_token刷新
			if ($access_token === '' && $expired_at <= $now) {
				self::setLastError(self::ERR_CODE_EXPIRED);
				return false;
			}
			$key2 = $refresh_token;
			$expired_in = self::ACCESS_TOKEN_EXPIRED_IN;
			$accessToken = hash_hmac('sha256', 'at' . $data, openssl_random_pseudo_bytes(16));
			$refreshToken = hash_hmac('sha256', 'rt' . $data, openssl_random_pseudo_bytes(16));
		} else {
			//获取code
			$key2 = $owner_id;
			$expired_in = self::AUTH_CODE_EXPIRED_IN;
			$accessToken = null;
			$refreshToken = hash_hmac('sha256', 'rt' . $data, openssl_random_pseudo_bytes(16));
		}
		self::setTokenInCache($app_id, $key2, [
				'access_token' => $accessToken, 
				'expired_in' => $expired_in, 
				'refresh_token' => $refreshToken
		]);
		
		return [$now + $expired_in, $refreshToken, $accessToken]; //expired_at, refresh_token
	}
	
}