<?php
namespace vendor\base;

use vendor\exceptions\InvalidParamException;
use vendor\exceptions\InvalidCallException;
use vendor\exceptions\UnknownException;

abstract class Record implements \ArrayAccess
{
	abstract public static function model();
	
	private function __construct(array $row)
	{
		$id = static::model()::primaryKeyVals($row);
		if (!$id) {
			throw new InvalidParamException();
		}
		$this->id = $id;
		$this->fields = $row;
	}
	
	private $id = [];
	private $fields = [];
	
	/**
	 * @param array $row
	 * @throws InvalidParamException
	 * @return \vendor\base\Record
	 */
	public static function load(array $row)
	{
		$model = static::model();
		$fields = $model::fields();
		$row = array_intersect_key($row, $fields);
		if (count($row) !== count($fields)) {
			throw new InvalidParamException();
		}
		return new static($row);
	}
	
	/**
	 * @param mixed $id
	 * @return NULL|\vendor\base\Record
	 */
	public static function one($id)
	{
		$model = static::model();
		$one = $model::one($id, $model::fields(false));
		return $one ? new static($one) : null;
	}
	
	public function id()
	{
		return $this->id;
	}
	
	/**
	 * @param array $vals
	 * @param bool $raw_value
	 * @return number
	 */
	public function save(array $vals, bool $raw_value = false)
	{
		$vals = array_intersect_key($vals, $this->fields);
		if ($vals) {
			$res = static::model()::_update($this->id, $vals, 1, $raw_value);
			$this->refresh();
		} else {
			$res = 0;
		}
		return $res;
	}
	
	/**
	 * @throws UnknownException
	 * @return boolean
	 */
	private function refresh()
	{
		$model = static::model();
		$one = $model::one($this->id, $model::fields(false));
		if (!$one) {
			throw new UnknownException();
		}
		$this->fields = $one;
		return true;
	}
	
	public function toArray()
	{
		return $this->fields;
	}
	
	public function offsetGet($offset)
	{
		if (!key_exists($offset, $this->fields)) {
			throw new InvalidParamException();
		}
		return $this->fields[$offset];
	}

	public function offsetExists($offset)
	{
		return key_exists($offset, $this->fields);
	}

	/**
	 * @deprecated
	 * {@inheritDoc}
	 * @see \ArrayAccess::offsetUnset()
	 */
	public function offsetUnset($offset)
	{
		throw new InvalidCallException();
	}

	/**
	 * @deprecated
	 * {@inheritDoc}
	 * @see \ArrayAccess::offsetSet()
	 */
	public function offsetSet($offset, $value)
	{
		throw new InvalidCallException();
	}

}