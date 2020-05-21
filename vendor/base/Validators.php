<?php
namespace vendor\base;

use vendor\db\Db;
use vendor\exceptions\DbErrorException;
use vendor\exceptions\InvalidConfigException;
use vendor\helpers\ModelHelper;
use vendor\helpers\ArrayHelper;

class Validators
{
	public static function requireValidate($fields, $vals, $allowedEmpties = [])
	{
		$fields = (array)$fields;
		foreach ($fields as $field) {
			if (key_exists($field, $vals)
					 && (	$vals[$field] || 
					 		is_int($vals[$field]) || 
					 		is_string($vals[$field]) && strlen(trim($vals[$field])) || 
					 		(isset($allowedEmpties[$field]) && in_array($vals[$field], $allowedEmpties[$field], true))
					 )
			) {
				continue;
			} else {
				return $field;
			}
		}
		return true;		
	}
	
	/**
	 * @param array $fields 
	 * [
	 * 		'field1' => ['model' => '', 'targets' => [], 'condition' => [], 'results' => [], 'allow_null' => false]
	 * 		'field2' => ['callback' => '', 'args' => [''], 'results' => [], 'instance' => true, 'allow_null' => false],
	 * ]
	 * @param Db $db
	 * @throws DbErrorException
	 * @return null|string|true
	 */
	public static function existValidate($configs, $vals, $existReturn = false, $caller = null, &$resKeeper = [])
	{
		foreach ($configs as $field => $checkers) {
			//支持一个字段对多个model存在性验证
			if (!isset($checkers[0])) {
				$checkers = [$checkers];
			}
			foreach ($checkers as $checker) {
				$res = true;
				if (isset($checker['allow_null']) && $checker['allow_null'] && $vals[$field] === null) {
					//nothing
				} elseif (isset($checker['model'])) {
					if (!ModelHelper::checkExist($res, $checker, $vals)) {
						continue;
					}
				} elseif (isset($checker['callback'])) {
					if (!Helpers::callback($res, $checker, $vals, $caller)) {
						continue;
					}
				} else {
					//unknown error
					throw new InvalidConfigException();
				}
				
				//保存数据
				if (ArrayHelper::not_empty('results', $checker)) {
					if (!isset($checker['results'][0])) {
						array_walk($res, function(&$row, $key) use ($checker) {
							$row = ArrayHelper::switch_keys($row, $checker['results']);
						});
					}
					$resKeeper[$field][] = $res;
				}
				
				if ($res && $existReturn || !$res && !$existReturn) {
					return $field;
				}
			}
		}
		return true;
	}
	
	/**
	 *  @param array $fields ['field' => ['min' => 0, 'max' => 0, 'truncate' => false]]
	 */
	public static function lengthValidate($fields, &$vals)
	{
		foreach ($fields as $field => $v) {
			$len = mb_strlen($vals[$field]);
			if (isset($v['min']) && $len < $v['min']) {
				return $field;
			}
			if (isset($v['max']) && $len > $v['max']) {
				if (!isset($v['truncate']) || !$v['truncate']) {
					return $field;
				}
				$vals[$field] = mb_substr($vals[$field], 0, $v['max']);
			}
		}
		return true;
	}
	
	/**
	 * @param array $fields ['field' => ['min' => 0, 'max' => 0, 'fix' => false]]
	 */
	public static function numberValidate($fields, &$vals)
	{
		foreach ($fields as $field => $v) {
			if (isset($v['min']) && $vals[$field] < $v['min']) {
				if (!isset($v['fix']) || !$v['fix']) {
					return $field;
				}
				$v[$field] = $v['min'];
			} elseif (isset($v['max']) && $vals[$field] > $v['max']) {
				if (!isset($v['fix']) || !$v['fix']) {
					return $field;
				}
				$v[$field] = $v['max'];
			}
		}
		return true;
	}
}