<?php
namespace user\models;

use vendor\base\Model;

/**
 * 仅针对湘雅
 */
class TempUserModel extends Model
{
	const NAME = 'user_ilab';
	
	const TEMP_RANDOM = 1;
	const TEMP_ILAB = 2;
	
	protected static $sets = [
			'id' => ['user_id'],
	];
	
	/**
	 * 仅学生
	 */
	public static function generateTempUser($user, $temp = self::TEMP_RANDOM, $ilabUserFromToken = null)
	{
		$temp = (int)$temp;
		
		if ($temp !== self::TEMP_RANDOM && $temp !== self::TEMP_ILAB) {
			return null;
		}
		
		$model = new UserModel();
		$res = $model->_set(null, [
				'school_id' => $user['school_id'],
				'group' => UserModel::GROUP_STUDENT,
				'username' => $user['username'],
				'password' => $user['password'],
				'name' => $user['name'],
		]);
		if ($res) {
            UserModel::_update(['id' => $res['id']], ['is_temp' => $temp]);
			if ($temp === self::TEMP_ILAB && $ilabUserFromToken) {
				self::setIlabUser($ilabUserFromToken, $res['id']);
			}
		}
		
		return $res;
	}
	
	/**
	 * 设置ilab用户
	 */
	protected static function setIlabUser($ilabUserFromToken, $user_id)
	{
		if (self::isIlabUser($user_id)) {
			return 0;
		}
		
		$res = self::_insert([
				'user_id' => $user_id,
				'ilab_id' => $ilabUserFromToken['id'],
				'ilab_un' => $ilabUserFromToken['un'],
				'ilab_dis' => $ilabUserFromToken['dis'],
				'created_at' => time()
		]);
		
		return $res;
	}
	
	public static function isIlabUser($user_id)
	{
		$exist = self::_select(['user_id' => $user_id], 'user_id');
		return !!$exist;
	}
	
	public static function getIlabUserByUserId($user_id)
	{
		$res = self::_select_one(['user_id' => $user_id]);
		return $res;
	}
	
	public static function getIlabUserByIlabId($ilab_id)
	{
		$res = self::_select_one(['ilab_id' => $ilab_id]);
		return $res;
	}
}