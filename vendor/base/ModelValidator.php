<?php
namespace vendor\base;

use vendor\exceptions\InvalidConfigException;
use vendor\helpers\ArrayHelper;

/**
 * 回调实例是model同类时，使用当前model
 */
class ModelValidator
{
	use ErrorTrait;
	
	const ERR_EMPTY = 1;
	const ERR_VALID = 2;
	const ERR_READONLY = 3;
	const ERR_REPEAT = 4;
	const ERR_LEN = 5;
	
	protected $model = null;
	protected $not_nulls = [];
	
	protected $exec = null;
	
	/**
	 * 动态
	 */
	protected $validates = [];
	
	public function __construct(ValidateModel $model)
	{
		$this->model = $model;
		$this->not_nulls = $model->not_nulls();
	}
	
	/**
	 * @param array $validates
	 * @param string $exec
	 * @param array $snapshot
	 * @param array $vals
	 */
	public function validate($validates, $exec, $snapshot, &$vals)
	{
		$this->exec = $exec;
		
		foreach ($validates as $type => $validate) {
			switch ($type) {
				case 'require' :
					if (!$this->requires($validate, $snapshot, $vals)) {
						return false;
					}
					break;
				case 'readonly' :	//针对 update
					if ($exec === 'update') {
						$vals = array_diff_key($vals, array_fill_keys($validate, null));
					}
					break;
				case 'exist' :
					if (!$this->exist($validate, $snapshot, $vals)) {
						return false;
					}
					break;
				case 'repeat' :
					if (!$this->repeat($validate, $snapshot, $vals)) {
						return false;
					}
					break;
				case 'filter' :
					if (!$this->filter($validate, $snapshot, $vals)) {
						return false;
					}
					break;
				case 'range' :
					$validate = array_intersect_key($validate, $vals);
					foreach ($validate as $fd => $range) {
						if (!in_array($vals[$fd], $range)) {
							$this->addError($fd, self::ERR_VALID);
							return false;
						}
					}
					break;
				case 'url' :
					$validate = array_intersect_key($validate, $vals);
					foreach ($validate as $fd) {
						if (filter_var($vals[$fd], FILTER_VALIDATE_URL) === false) {
							$this->addError($fd, self::ERR_VALID);
							return false;
						}
					}
					break;
				case 'regular' :
					$validate = array_intersect_key($validate, $vals);
					foreach ($validate as $fd => $pattern) {
						if (!preg_match($pattern, $vals[$fd])) {
							$this->addError($fd, self::ERR_VALID);
							return false;
						}
					}
					break;
				case 'string' :
					$validate = array_intersect_key($validate, $vals);
					$valid = Validators::lengthValidate($validate, $vals);
					if ($valid !== true) {
						$this->addError($valid, self::ERR_LEN);
						return false;
					}
					break;
				case 'number' :
					$validate = array_intersect_key($validate, $vals);
					$valid = Validators::numberValidate($validate, $vals);
					if ($valid !== true) {
						$this->addError($valid, self::ERR_VALID);
						return false;
					}
					break;
			}
		}
		
		return true;
	}
	
	/**
	 * @param array $validate
	 * @param array $vals
	 * @return boolean
	 */
	protected function requires($validate, $snapshot, $vals)
	{
		if (is_string($validate[0])) {
			$validates = [$validate];
		} elseif (is_array($validate[0])) {
			$validates = isset($validate['when']) || isset($validate['!when']) || isset($validate['allowedEmpties']) ? [$validate] : $validate;
		} else {
			throw new InvalidConfigException();
		}
		$val_keys = array_keys($vals);
		$preview = $vals + $snapshot;
		
		foreach ($validates as $validate) {
			if (is_array($validate[0])) {
				if (Helpers::when($validate, $preview) === false) {
					continue;
				}
				$requires = $validate[0];
			} else {
				$requires = $validate;
			}
			if ($this->exec === 'update') {
				$requires = array_intersect($requires, $val_keys);
			}
			if (!$requires) {
				continue;
			}
			$valid = Validators::requireValidate($requires, $vals, isset($validate['allowedEmpties']) ? $validate['allowedEmpties'] : []);
			if ($valid !== true) {
				$this->addError($valid, self::ERR_EMPTY);
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * @param array $validate
	 * @param array $snapshot
	 * @param array $vals
	 * @throws InvalidConfigException
	 */
	protected function filter($validate, $snapshot, &$vals)
	{
		foreach ($validate as $fd => $filter) {
			//检查字段
			if (!key_exists($fd, $vals) && !(isset($filter['always']) && $filter['always'])) {
				continue;
			}
			//设置字段参数
			$filter['args'] = $filter['args'] ?? [];
			$filter['args'][] = $fd;
			//开始执行
			$filterResult = null;
			if (!Helpers::callback($filterResult, $filter, $vals + $snapshot, $this->model)) {
				continue;
			}
			//处理结果
			if ($filterResult === false) {
				$this->addError($fd, self::ERR_VALID);
				return false;
			}
			if ($filterResult === true) {
				continue;
			}
			if (isset($filter['results'])) {
				if (!is_array($filterResult)) {
					throw new InvalidConfigException();
				}
				if (isset($filterResult[0])) {
					if (count($filter['results']) > count($filterResult)) {
						throw new InvalidConfigException();
					}
					foreach ($filter['results'] as $rfd) {
						$vals[$rfd] = array_shift($filterResult);
					}
					!$filterResult ?: $vals[$fd] = array_shift($filterResult);
				} else {
					foreach ($filter['results'] as $rfd) {
						!key_exists($rfd, $filterResult) ?: $vals[$rfd] = $filterResult[$rfd];
					}
					!key_exists($fd, $filterResult) ?: $vals[$fd] = $filterResult[$fd];
				}
			} else {
				$vals[$fd] = $filterResult;
			}
		}
		
		//去掉vals里面的等值
		$vals = ArrayHelper::diff_assoc($vals, $snapshot);
		
		return true;
	}
	
	/**
	 * @param array $validate
	 * @param array $snapshot
	 * @param array $vals
	 * @throws InvalidConfigException
	 * @throws \Exception
	 * @return boolean
	 */
	protected function exist($validate, $snapshot, &$vals)
	{
		$validate = array_intersect_key($validate, $vals);
		if (!$validate) {
			return true;
		}
		
		$resKeeper = [];
		$valid = Validators::existValidate($validate, $vals + $snapshot, false, $this->model, $resKeeper);
		if ($valid !== true) {
			if ($valid) {
				$this->addError($valid, self::ERR_VALID);
				return false;
			} else {
				//unknow error
				throw new \Exception();
			}
		}
		
		//衍生值修改
		foreach ($resKeeper as $multi_results) {
			foreach ($multi_results as $results) {
				foreach ($results as $result) {
					$vals = $result + $vals;;
				}
			}
		}
		
		return true;
	}
	
	/**
	 * @param array $validate
	 * @param array $snapshot
	 * @param array $vals
	 * @throws InvalidConfigException
	 * @throws \Exception
	 * @return boolean
	 */
	protected function repeat($validate, $snapshot, $vals)
	{
		//规范化
		if (is_string($validate[0])) {
			$validates = [$validate];
		} elseif (is_array($validate[0])) {
			if (isset($validate['when']) || isset($validate['!when'])) {
				$validates = [$validate];
			} else {
				$validates = $validate;
			}
		} else {
			throw new InvalidConfigException();
		}
		
		foreach ($validates as $validate) {
			if (is_array($validate[0])) {
				if (!Helpers::when($validate, $vals + $snapshot)) {
					continue;
				}
				$validate = $validate[0];
			}
			
			$validate_kfds = array_fill_keys($validate, null);
			$validate_vals = array_intersect_key($vals, $validate_kfds);
			if (!$validate_vals) {
				return true;
			}
			$validate_vals += array_intersect_key($snapshot, $validate_kfds);
			if (count($validate_vals) !== count($validate)) {
				throw new InvalidConfigException();
			}
			$validate_configs =  [
					$validate[0] => [
							'model' => get_class($this->model),
							'targets' => array_combine($validate, $validate)
					]
			];
			$valid = Validators::existValidate($validate_configs, $validate_vals, true);
			if ($valid !== true) {
				if (!$valid) {
					//unknow error
					throw new \Exception();
				} else {
					$this->addError($validate, self::ERR_REPEAT);
					return false;
				}
			}
		}
		
		return true;
	}
}