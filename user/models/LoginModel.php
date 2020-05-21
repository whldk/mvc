<?php
namespace user\models;

use vendor\base\ErrorTrait;
use vendor\base\AppTrait;

class LoginModel
{
	use ErrorTrait, AppTrait;
	
	const ERR_VALID = 2;
	
	const WINDOW_SIZE = 80;
	
	public function login($school_alias, $username, $password, $remember = 0)
	{
		$username = (string)$username;
		$password = (string)$password;
		$remember = $remember ? true : false;
	
		if ($school_alias) {
			$school = SchoolModel::getByAlias($school_alias);
			if (!$school) {
				return $this->addError('school_alias', self::ERR_VALID);
			}
			$school_id = $school[0]['id'];
		} else {
			$school_id = null;
		}
	
		$identity = IdentityModel::findByName($school_id, $username);

		if (!$identity) {
			return $this->addError('username', self::ERR_VALID);
		}

		if (!$this->verify_password($password, $identity['password'])) {
			return $this->addError('password', self::ERR_VALID);
		}

		return $this->user->login($identity, $remember);
	}
	
	public function loginByUserId($user_id, $group, $remember = 0)
	{
		$identity = IdentityModel::findById([$user_id, $group]);
	
		if (!$identity) {
			return $this->addError('user_id', self::ERR_VALID);
		}
	
		return $this->user->login($identity, $remember);
	}
	
	public static function verify_password($password, $hash)
	{
	    if (LOGIN_HASH) {
            if (self::decrypt_password($password)) {
                return password_verify($password, $hash);
            }
            return false;
        } else {
            return password_verify($password, $hash);
        }
	}
	
	public static function decrypt_password(&$password)
	{
		$prKey = file_get_contents(RSA_PR_KEY);
		if (!openssl_private_decrypt(base64_decode(str_replace(' ', '+', $password)), $password, $prKey)) {
			return false;
		}
		
		$password = json_decode($password, true);
		if (count($password) === 3 && isset($password[0], $password[1], $password[2])) {
			$window = abs(time() - $password[0]);
			if ($window > self::WINDOW_SIZE) {
				return false;
			}
			
			$session = static::getSession();
			$ts = $session->get('ts');
			$nonce = $session->get('nonce');
			if ($ts != $password[0] || $nonce != $password[1]) {
				return false;
			}
			$password = $password[2];
			return true;
		}  
		
		return false;
	}
}