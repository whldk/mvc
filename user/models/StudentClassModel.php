<?php
namespace user\models;

use vendor\base\ValidateModel;

class StudentClassModel extends ValidateModel
{
	const NAME = 'student_class';
	
	protected static $sets = [
			'id' => ['class_id', 'user_id'],	//one user => multiple class
	];
	
	protected static $fields = [
			'school_id' => '',
			'user_id' => null,
			'class_id' => null,
			'is_main' => 0,
			'created_at' => null
	];
	
	protected static $filters = [
			'before' => [
					'b' => ['is_main'],
					's' => ['school_id', 'class_id', 'user_id'],
					'ts' =>  ['ct' => 'created_at'],
			],
	];
	
	protected static $validates = [
			'exist' => [
					'user_id' => [
							'model' => StudentModel::class,
							'targets' => ['user_id' => 'id'],
					],
					'class_id' => [
							'model' => SchoolClassModel::class,
							'targets' => ['class_id' => 'id'],
							'condition' => ['status' => SchoolClassModel::STATUS_ACTIVE],
					]
			],
	];
	
	protected static $hasOne = [
			SchoolClassModel::class => ['key' => 'class_id', 'rel_key' => 'id'],
	];
	
	public static function view()
	{
		/* @var IdentityModel $identity */
		$user = static::getUser();
		$identity = $user->getIdentity();
		if ($user->getRole() === 'school_admin') {
			return [
					'school_id' => $identity['school_id'],
			];
		} else {
			return [
					'school_id' => $identity['school_id'],
					'class_id' => $identity->getClassId()
			];
		}
	}
	
	public function _set($user_id, $class_id, $vals)
	{
		return $this->set_one(['class_id' => $class_id, 'user_id' => $user_id] + $vals);
	}
	
	public function _del($user_id, $class_id)
	{
		return $this->del_one(['class_id' => $class_id, 'user_id' => $user_id]);
	}
	
	protected function after_insert($fields, $primaryKeyVals)
	{
		if ($fields['is_main'] == 1) {
			$this->setNotMain($fields);
		}
		return parent::after_insert($fields, $primaryKeyVals);
	}
	
	protected function before_update($primaryKeyVals, $snapshot, &$vals)
	{
		if ($snapshot['is_main'] == 1) {
			//主班级不允许更新为副班级
			unset($vals['is_main']);
		} elseif (isset($vals['is_main']) && $vals['is_main'] == 1) {
			//副班级更新为主班级
			$this->setNotMain($snapshot);
		}
		return parent::before_update($primaryKeyVals, $snapshot, $vals);
	}
	
	/**
	 * XXX 仅供事务内运行
	 */
	protected function setNotMain(array $snapshot)
	{
		//锁定行
		static::getDb()->select_for_update(['user_id'])->from(self::NAME)
		->where(['user_id' => $snapshot['user_id']])
		->result();
		//更新数据
		self::_update([
				'user_id' => $snapshot['user_id'],
				['<>', 'class_id', $snapshot['class_id']]
		], [
				'is_main' => 0
		]);
	}
	
	protected function before_delete($primaryKeyVals, $snapshot)
	{
		if ($snapshot['is_main'] == 1) {
			return $this->addError('is_main', self::ERR_VALID);
		}
		return parent::before_delete($primaryKeyVals, $snapshot);
	}
	
	public static function filterStudents($student_ids)
	{
		$res = self::_select([
				'id' => $student_ids,
				'group' => SchoolUserModel::GROUP_STUDENT,
				'status' => SchoolUserModel::STATUS_ACTIVE
		], ['id']);
		
		return $res ? array_column($res, 'id') : [];
	}
	
	public static function getClassIds($school_id, $user_id)
	{
		$res = self::_select(['school_id' => $school_id, 'user_id' => $user_id], ['class_id', 'is_main'], 0, [['is_main', 'DESC']]);
		return $res ? array_column($res, 'class_id') : [];
	}
}