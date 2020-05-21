<?php
namespace vendor\base;

use vendor\exceptions\InvalidConfigException;
use vendor\exceptions\ServerErrorException;
use vendor\exceptions\UnknownException;
use vendor\helpers\ModelHelper;

/**
 * @tutorial 具有验证功能的Model
 */
class ValidateModel extends Model
{
	protected static $not_nulls = [];
	
	/**
	 * 例如
	 * [
	 *		'before' => [
	 *			's' => ['name', 'category', 'chief_complaint', 'medical_history'],
	 *			'html' => ['instruction']
	 *			'b' => ['isRed'],
	 * 			'i' => ['inquiry_limit', 'exam_limit', 'test_limit'],
	 * 			'f' => ['height'],
	 * 			'ts' =>  ['ct' => 'created_at', 'mt' => 'updated_at']
	 * 			'img' => ['img'], //[['img']],
	 * 			'map' => [[['name'], 'callback' => []]]
	 * 			'ignore' => [[], 'when'=> []] or ['xxx', 'xxx']	//when/!when
	 *		],
	 *		'after' => [
	 *			'json' => ['feedback'],
	 *		],
	 *	]
	 * @var array
	 */
	protected static $filters = [];
	
	/**
	 *  如果子类需要覆盖此属性的值，必须新声明此变量，并且覆盖相应的validates()静态方法
	 * @see ValidateModel::validates()
	 * @tutorial 例如
	 * [
	 *		'require' => [['name', 'gender], 'allowedEmpties' => ['name' => [null]]],
	 *		'readonly' => ['name'],
	 *		'exist' => [
	 *			'field1' => ['model' => '', 'targets' => [], 'results' => [], 'allow_null' => false]
	 *			'field2' => ['callback' => '', 'args' => [0=>'', 1=>'', 2=>''], 'allow_null' => false]
	 *		],
	 *		'repeat' => ['name'],	// [field1, field2], when/!when
	 *		'range' => ['name' => ['xiaoming', 'xiaohong'], 'gender' => ['male', 'female']],
	 *		'filter' => [[], 'callback' => [], 'args' => ['item_id']],
	 *		'regular' => ['name' => '/^abc$/', 'gender' => '/^abc$/'],
	 *		'string' =>  ['field' => ['min' => 0, 'max' => 0, 'truncate' => false]],
	 *		'number' =>  ['field' => ['min' => 0, 'max' => 0, 'fix' => false]],
	 *	]
	 * @var array
	*/
	protected static $validates = [];

	protected static $vPriority = [
			'require' => 1,
			'readonly' => 1,
			'range' => 2,
			'exist' => 3,
			'filter' => 4,
			'url' => 5,
			'regular' => 5,
			'string' => 5,
			'number' => 5,
			'repeat' => 6
	];
	
	/**
	 * [
	 *		'id' => [
	 *				['model' => xxxModel::class, 'targets' => ['id' => 'forum_id']],
	 *				['model' => self::class, 'targets' => ['id' => 'pid']]],
	 *		]
	 * ];
	 */
	protected static $constraints = [];
	
	/**
	 * [
    		'id' => [
    				['model' => xxxModel::class, 'targets' => ['id' => 'case_id'], 'when' => []],
    				['model' => self::class, 'targets' => ['id' => 'case_id'], 'when' => []],
    		]
    ]
	 * @var array
	 */
	protected static $cascades = [];
	
	protected static function okuFields(array $fields)
	{
		//剔除readonly字段，暂不剔除id字段
		$validates = static::validates();
		$readonly = isset($validates['readonly']) ? array_fill_keys($validates['readonly'], null) : [];
		
		$okuFields = parent::okuFields($fields);
		
		return array_diff_key($okuFields, $readonly);
	}
	
	final public static function not_nulls()
	{
		return static::$not_nulls ?: array_keys(static::$fields, null);
	}
	
	public static function filters()
	{
		return static::$filters;
	}
	
	/**
	 * @tutorial 可以设置上下文切换validates
	 * validate 的顺序需要按照[vPriority]排序
	 * @see ValidateModel::orderValidates()
	 * @see ValidateModel::$vPriority
	 * @see ValidateModel::$validates
	 */
	public static function validates()
	{
		self::orderValidates(static::$validates);
		return static::$validates;
	}
	
	final protected static function orderValidates(&$validates)
	{
		$vp = $validates;
		foreach ($vp as $v => $validate) {
			$vp[$v] = isset(static::$vPriority[$v]) ? static::$vPriority[$v] : PHP_INT_MAX;
		}
		array_multisort($vp, $validates);
	}
	
	public static function constraints()
	{
		return static::$constraints;
	}
	
	public static function cascades()
	{
		return static::$cascades;
	}
	
	/**
	 * @var ModelFilter $modelFilter
	 */
	protected $modelFilter = null;
	
	protected function modelFilter()
	{
		if (!$this->modelFilter) {
			$this->modelFilter = new ModelFilter($this);
		}
		return $this->modelFilter;
	}
	
	/**
	 * @var ModelValidator $modelValidator
	 */
	protected $modelValidator = null;
	
	protected function modelValidator()
	{
		if (!$this->modelValidator) {
			$this->modelValidator = new ModelValidator($this);
		}
		return $this->modelValidator;
	}
	
	protected function internal_set($primaryKeyVals, $vals)
	{
		//检查key
		$idKeys = self::primaryKeys();
		$sets = static::sets();
		if (isset($sets['auto_inc'])) {
			$idKeys = array_diff($idKeys, [$sets['auto_inc']]);
		}
		if (isset($sets['hash_id'])) {
			$idKeys = array_diff($idKeys, [$sets['hash_id']]);
		}
		
		$keyVals = array_intersect_key($primaryKeyVals, array_fill_keys($idKeys, null));
		
		if (count($idKeys) !== count($keyVals)) {
			return $this->addError($idKeys, self::ERR_EMPTY);
		}
		
		//清空错误
		$this->clearErrors();
		
		$res = null;
		list($exec, $snapshot) = $this->before_set($primaryKeyVals, $vals);
		switch ($exec) {
			case 'insert' :
				$res = $this->internal_insert($vals + $snapshot);
				break;
			case 'update' :
				$res = $this->internal_update($primaryKeyVals, $snapshot, $vals);
				break;
			case 'delete' :
				$res = $this->internal_delete($primaryKeyVals, $snapshot);
		}
		return $res;
	}
	
	protected function updates_of_insert($fields, $primaryKeyVals, $_id = null)
	{
		return [];
	}
	
	protected function before_insert(&$fields)
	{
	    return true;
	}
	
	protected function after_insert($fields, $primaryKeyVals)
	{
		return $primaryKeyVals;
	}
	
	protected function internal_insert($fields)
	{
		$res = $this->callInTransaction(function () use ($fields) {
		    if (!$this->before_insert($fields)) {
		        throw new UnknownException();
		    }
			//先插入
			self::_insert($fields);
			
			$where = [];
			$updates = [];
			$_id = null;
			$primaryKeyVals = [];
			
			$sets = static::$sets;
			
			if (isset($sets['hash_id'])) {
				$_id = static::getDb()->get_last_insert_id();
				if (!$_id) {
					throw new ServerErrorException();
				}
				$hashid = self::hashids()->encode($_id);
				if (!$hashid) {
					throw new ServerErrorException();
				}
				$primaryKeyVals[$sets['hash_id']] = $hashid;
				if (isset($sets['id']) && $sets['id'] !== [$sets['hash_id']]) {
					$primaryKeyVals += array_intersect_key($fields, array_fill_keys($sets['id'], null));
				}
				$where[$sets['auto_inc']] = $_id;
				$updates[$sets['hash_id']] = $hashid;
			} elseif (isset($sets['auto_inc'])) {
				$_id = static::getDb()->get_last_insert_id();
				if (!$_id) {
					throw new ServerErrorException();
				}
				$primaryKeyVals[$sets['auto_inc']] = $_id;
				if (isset($sets['id']) && $sets['id'] !== [$sets['auto_inc']]) {
					$primaryKeyVals += array_intersect_key($fields, array_fill_keys($sets['id'], null));
				}
				$where[$sets['auto_inc']] = $_id;
			} else {
				$primaryKeyVals = array_intersect_key($fields, array_fill_keys($sets['id'], null));
				$where = $primaryKeyVals;
			}
			
			$updates += $this->updates_of_insert($fields, $primaryKeyVals, $_id);
			$updates && $this->_update($where, $updates);
			
			$fields = $updates + $fields;
			
			return $this->after_insert($fields, $primaryKeyVals);
		});
		
		return $res;
	}
	
	protected function internal_update($primaryKeyVals, $snapshot, $vals)
	{
	    $res = $this->callInTransaction(function () use ($primaryKeyVals, $snapshot, $vals) {
	        if (!$this->before_update($primaryKeyVals, $snapshot, $vals)) {
	        	if (!$this->errors) {
	        		$this->addError(self::primaryKeys(), self::ERR_VALID);
	        	}
	            throw new UnknownException();
	        }
	        $res = self::_update($primaryKeyVals, $vals);
	        return $this->after_update($res, $primaryKeyVals, $snapshot, $vals);
	    });
	    return $res;
	}
	
	protected function before_update($primaryKeyVals, $snapshot, &$vals)
	{
	    return true;
	}
	
	protected function after_update($res, $primaryKeyVals, $snapshot, $vals)
	{
		return $res;
	}
	
	/**
	 * @param array $snapshot
	 * @throws \Exception
	 * @return null|array
	 */
	protected function before_delete($primaryKeyVals, $snapshot)
	{
		return true;
	}
	
	protected function after_delete($res, $primaryKeyVals, $snapshot)
	{
		return $res;
	}
	
	protected function check_constraints($primaryKeyVals, $snapshot)
	{
		//检查constraints
		if ($constraints = static::constraints()) {
			$valid = Validators::existValidate($constraints, $snapshot, true);
			if ($valid !== true) {
				if (!$valid) {
					//未知错误
					throw new \Exception();
				} else {
					return $this->addError($valid, self::ERR_VALID);
				}
			}
		}
		return true;
	}
	
	protected function cascaded_delete($primaryKeyVals, $snapshot)
	{
		$cascades = static::cascades();
		if (!$cascades) {
			return true;
		}
		
		foreach ($cascades as $deletes) {
			if (!isset($deletes[0])) {
				//规范格式
				$deletes = [$deletes];
			}
			foreach ($deletes as $delete) {
				$tmp = 0;
				if (isset($delete['model'])) {
					ModelHelper::delete($tmp, $delete, $snapshot);
				} elseif (isset($delete['callback'])) {
					Helpers::callback($tmp, $delete, $snapshot, $this);
				} else {
					throw new InvalidConfigException();
				}
				if ($tmp === null || $tmp === false) {
					throw new \Exception();
				}
			}
		}
		
		self::trashFiles($snapshot);
		
		return true;
	}
	
	protected function internal_delete($primaryKeyVals, $snapshot)
	{
		$res = $this->callInTransaction(function () use ($primaryKeyVals, $snapshot) {
			if (!$this->before_delete($primaryKeyVals, $snapshot) || !$this->check_constraints($primaryKeyVals, $snapshot)) {
				if (!$this->errors) {
					$this->addError(self::primaryKeys(), self::ERR_VALID);
				}
			    throw new UnknownException();
			}
			$res = $this->_delete($primaryKeyVals);
			if (!$res) {
				return $res;
			}
			$this->cascaded_delete($primaryKeyVals, $snapshot);
			return $this->after_delete($res, $primaryKeyVals, $snapshot);
		});
		
		return $res;
	}
	
	protected function get_exec($primaryKeyVals, $vals)
	{
		$exec = null;
		$checkExist = false;
	
		$sets = static::$sets;
		if ($vals === null) {
			$exec = 'delete';
			$checkExist = true;
		} elseif (!isset($sets['auto_inc'])) {
			$checkExist = true;
		} else {
			if (in_array($sets['auto_inc'], $sets['id']) || isset($sets['hash_id']) && in_array($sets['hash_id'], $sets['id'])) {
				if (isset($primaryKeyVals[$sets['auto_inc']]) || isset($sets['hash_id']) && isset($primaryKeyVals[$sets['hash_id']])) {
					$exec = 'update';
					$checkExist = true;
				} else {
					$exec = 'insert';
				}
			} else {
				$checkExist = true;
			}
		}
	
		if ($checkExist === true) {
			$one = static::checkExist($primaryKeyVals);
			if ($one) {
				if ($exec === null) {
					$exec = 'update';
				}
			} else {
				if ($exec === null) {
					$exec = 'insert';
				} else {
					return $this->addError($sets['id'], self::ERR_VALID);
				}
			}
		}
	
		return $exec === 'insert' ? [$exec, static::fields()] : [$exec, $one];
	}
	
	protected static function checkExist($primaryKeyVals)
	{
		$one = self::_select_one($primaryKeyVals, static::fields(false));
		return $one;
	}
	
	/**
	 * 因为vals的引用性，vals自身会被修改和过滤
	 * @param array $primaryKeyVals
	 * @param array $vals
	 * @return null|array
	 */
	protected function before_set($primaryKeyVals, &$vals)
	{
		list($exec, $snapshot) = $this->get_exec($primaryKeyVals, $vals);
		
		switch ($exec) {
			case 'insert' :
				$vals = $primaryKeyVals + $vals;
				break;
			case 'update' :
				$vals = array_diff_key($vals, $primaryKeyVals);
				break;
			case 'delete' :
			    return [$exec, $snapshot];
			default :
				return null;
		}
		
		$modelFilter = $this->modelFilter();
		
		//filter before
		$modelFilter->before_filter($snapshot, $vals);
		
		//validate
		if ($this->validate($exec, $snapshot, $vals) !== true) {
			return null;
		}
		
		//filter after
		$modelFilter->after_filter($snapshot, $vals);
	
		return [$exec, $snapshot];
	}
	
	/**
	 * @tutorial 删除图片以及其他类型的文件
	 * @param array $snapshot
	 */
	protected static function trashFiles($snapshot)
	{
		//trash file
		$filters = static::filters();
		
		$urlKeys = [];
		
		foreach (['before', 'after'] as $step) {
		    if (!isset($filters[$step])) {
		        continue;
		    }
		    $tmp = $filters[$step];
		    foreach (['img', 'file'] as $type) {
		        if (isset($tmp[$type])) {
		            $files = isset($tmp[$type][0]) && is_array($tmp[$type][0]) ? $tmp[$type][0] : $tmp[$type];
		            $urlKeys = array_filter(array_merge($urlKeys, $files));
		        }
		    }
		}
		
		foreach ($urlKeys as $urlKey) {
		    UploadManager::trash($snapshot[$urlKey]);
		}
	}
	
	/**
	 * 此处可以重写方法，并且切换上下文，获取不同的validates设置值
	 * @see ValidateModel::validates()
	 * @param string $exec
	 * @param array $snapshot
	 * @param array $vals
	 * @return boolean
	 */
	public function validate($exec, $snapshot, &$vals)
	{
		$validates = static::validates();
		return $this->internal_validate($validates, $exec, $snapshot, $vals);
	}
	
	/**
	 * 根据上下文或当前值，动态修改$validates，再调用父类的次方法
	 * @param array $validates
	 * @param string $exec
	 * @param array $snapshot
	 * @param array $vals
	 * @return boolean
	 */
	protected function internal_validate($validates, $exec, $snapshot, &$vals)
	{
		$validator = $this->modelValidator();
		
		$this->clearErrors();
		$res = $validator->validate($validates, $exec, $snapshot, $vals);
		if ($res === false) {
			if (!$this->errors) {
				$this->errors = $validator->errors();
				$this->setLastError($this->errors);
			}
		}
		
		return $res;
	}
	
	public function set_one(array $vals)
	{
		$vals = (array)$vals;
		$id = static::primaryKeyVals($vals);
		return $this->internal_set($id, $vals);
	}
	
	public function del_one($vals)
	{
	    $id = static::primaryKeyVals($vals);
		return $this->internal_set($id, null);
	}
	
	protected function before_copy(&$one, $vals)
	{
		//检查新建对象id
		$sets = static::$sets;
		if (isset($sets['auto_inc']) && in_array($sets['auto_inc'], $sets['id'])) {
			$one[$sets['auto_inc']] = null;
			$newIdVals = self::primaryKeyVals($one);
		} elseif (isset($sets['hash_id']) && in_array($sets['hash_id'], $sets['id'])) {
			$one[$sets['hash_id']] = null;
			$newIdVals = self::primaryKeyVals($one);
		} else {
			//need new id
			$newIdVals = self::primaryKeyVals($vals);
		}
		
		if (!$newIdVals) {
			return $this->addError(self::primaryKeys() ?: 'id', self::ERR_VALID);
		}
		
		$one = array_diff_key($vals, $newIdVals) + array_diff_key($one, $newIdVals);
		
		return $newIdVals;
	}
	
	/**
	 * @param mixed $idVals
	 * @param array $vals 额外的更新字段（一些情况下还用于生成新的id）
	 * @return array|null
	 */
	public function copy_one($idVals, $vals = [])
	{
		$one = self::one($idVals, array_keys(static::$fields));
		if (!$one) {
			return $this->addError(self::primaryKeys() ?: 'id', self::ERR_VALID);
		}
		
		if ($newIdVals = $this->before_copy($one, $vals)) {
			$res = $this->internal_set($newIdVals, $one);
			return $res ? $res + $one : null;
		}
		
		return null;
	}
}