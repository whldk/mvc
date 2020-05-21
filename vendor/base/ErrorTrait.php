<?php
namespace vendor\base;

trait ErrorTrait
{
	//全部唯一
	private static $last_error;
	
	protected $errors = [];
	
	protected function addError($fields, $error)
	{
		self::$last_error = [];
		
		$fields = (array)$fields;
		foreach ($fields as $field) {
			$this->errors[$field] = $error;
		}
		
		self::$last_error[$field] = $error;
		
		return null;
	}

	public function errors()
	{
		//@todo testing
		return $this->errors ?: self::$last_error;
	}

	public function clearErrors()
	{
		self::$last_error = [];
		$this->errors = [];
	}
	
	public static function lastError()
	{
		return self::$last_error;
	}
	
	protected static function setLastError($error)
	{
		self::$last_error = $error;
	}
	
	public function mergeLastError()
	{
		$this->errors += self::$last_error;
	}
}