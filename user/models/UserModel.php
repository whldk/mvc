<?php
namespace user\models;

use vendor\base\ValidateModel;

class UserModel extends ValidateModel
{
	const NAME = 'user';
	
	const INIT_password = '123456';
	
	const FILE_PRIVATE_KEY = 'rsa_private_key.pem';
	
	const GROUP_SCHOOL_ADMIN = 1;
	const GROUP_TEACHER = 2;
	const GROUP_STUDENT = 3;
	
	const STATUS_DELETED = 0;
	const STATUS_ACTIVE = 1;
	
	const G_FEMALE = 0;
	const G_MALE = 1;
	const G_UNKNOWN = 2;
	
	const groups = [
			self::GROUP_SCHOOL_ADMIN,
			self::GROUP_TEACHER,
			self::GROUP_STUDENT,
	];
	
	const statuses = [
			self::STATUS_ACTIVE,
			self::STATUS_DELETED
	];
	
	protected static $fields = [
			'id' => null,
			'school_id' => '',
			'group' => null,
			'username' => null,
			'iusername' => null,
			'password' => null,
			'name' => null,
			'gender' => self::G_UNKNOWN,
			'avatar' => '',
			'intro' => '',
			'intro_page' => '',
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
	
	protected static $sets = [
			'auto_inc' => '_id',
			'hash_id' => 'id',
			'id' => ['id'],
	];
	
	protected static $filters = [
			'before' => [
					's' => ['school_id', 'username', 'real_name', 'intro_page'],
					'html' => ['intro'],
					'i' => ['group', 'gender'],
					'ts' =>  ['ct' => 'created_at', 'mt' => 'updated_at'],
					'img' => [
							['avatar'],
							'resample' => [
									'avatar' => [
											'to' => [[150], [100], [50]],
									],
							]
					],
					'map' => [
							'password' => [
									'callback' => [self::class, 'openssl_private_decrypt']
							]
					],
			],
			'after' => [
					'map' => [
							'password' => [
									'callback' => [self::class, 'generate_password_hash']
							],
							'iusername' => [
									'callback' => [self::class, 'generate_iusername'],
									'args' => ['username'],
							],
					],
			]
	];
	
	protected static $validates = [
			'require' => ['group', 'username', 'password', 'name'],
			'readonly' => ['school_id', 'username', 'group'],
			'range' => [
					'status' => self::statuses,
					'group' => self::groups,
					'gender' => [self::G_FEMALE, self::G_MALE, self::G_UNKNOWN],
			],
			'regular' => [
					'username' => '/^[a-zA-Z0-9_]+$/',
					'password' => '/^[a-zA-Z0-9!#$%&\'*+\\/=?^_`{|}~\-:\.@()\[\]";,]+$/'
			],
			'url' => ['intro_page'],
			'string' => [
					'username' => ['min' => 2, 'max' => 255, 'truncate' => false],
					'password' => ['min' => 6, 'max' => 255, 'truncate' => false],
					'name' => ['min' => 1, 'max' => 255, 'truncate' => false],
					'intro' => ['max' => 20000, 'truncate' => false],
			],
			'exist' => [
					'school_id' => ['model' => SchoolModel::class, 'targets' => ['school_id' => 'id']],
			],
			'filter' => [
					'username' => [
							'callback' => [self::class, 'validateUsername'],
							'instance' => true,
					],
			],
	];
	
	protected static $searchFilter = ['username' => 'username', 'name' => 'name'];
	
	protected static $priorOrder = [[self::NAME . '._id', 'desc']];
	protected static $orderFilter = [];
	
	protected static $matchFilter = ['status', 'group'];
	
	public function validateUsername($username)
	{
		$repeat = UserModel::_select(['username' => $username], ['username'], 1);
		if ($repeat) {
			$this->addError('username', self::ERR_REPEAT);
			return false;
		}
		return true;
	}
	
	public static function listFields()
	{
		$fields = array_keys(static::$fields);
		return array_diff($fields, self::$sensitiveFields, ['intro']);
	}
	
	public static function getFields()
	{
		$fields = array_keys(static::$fields);
		return array_diff($fields, self::$sensitiveFields);
	}

	public static function _list($school_id, $group = null, $status = self::STATUS_ACTIVE, $search = [], $size = self::PAGESIZE, $page = 0)
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
	
	public function set_avatar($id, $zoom, $x, $y, $w, $h)
	{
		$filter = &self::$filters['before']['img']['resample']['avatar'];
		$back = $filter;
		
		$filter = [
				'zoom' => $zoom < 1 && $zoom > 0 ? (float)$zoom : 1,
				'x' => $x ? (int)$x : 0,
				'y' => $y ? (int)$y : 0,
				'w' => $w ? (int)$w : 0,
				'h' => $h ? (int)$h : 0,
		] + $filter;
		
		$res = $this->set_one(['id' => $id, 'avatar' => $_FILES['avatar']]);
		
		$filter = $back;
		
		if ($res) {
			$one = self::one($id, 'avatar');
			$res = $one ? $one['avatar'] : '';
		} else {
			$res = '';
		}
		
		return $res;
	}
	
	/**
	 * {@inheritDoc}
	 * @see \vendor\base\ValidateModel::before_delete()
	 */
	protected function before_delete($primaryKeyVals, $snapshot)
	{
		if (time() - $snapshot['created_at'] > 86400) {
			return $this->addError('created_at', self::ERR_VALID);
		}
		
		return parent::before_delete($primaryKeyVals, $snapshot);
	}
	
	public static function generate_password_hash($password)
	{
		return password_hash($password, PASSWORD_DEFAULT);
	}
	
	public static function generate_iusername($username)
	{
		return strtolower($username);
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