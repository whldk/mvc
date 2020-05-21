<?php
namespace vendor\helpers;

class StringHelper
{
	/**
	 * @return int the number of bytes in the given string.
	 */
	public static function byteLen($string)
	{
		return mb_strlen($string, '8bit');
	}
	
	/**
	 * @return string the extracted part of string, or FALSE on failure or an empty string.
	 * @see http://www.php.net/manual/en/function.substr.php
	 */
	public static function byteSubstr($string, $start, $length = null)
	{
		return mb_substr($string, $start, $length === null ? mb_strlen($string, '8bit') : $length, '8bit');
	}
	
	public static function mbRtrim($str, $chars)
	{
		if (!$chars) {
			return $str;
		}
		
		$len = mb_strlen($chars);
		$charArr = [];
		for ($i = 0; $i < $len; $i++) {
			$charArr[] = mb_substr($chars, $i, 1);
		}
		
		$len = mb_strlen($str);
		while ($len) {
			$len--;
			$char = mb_substr($str, $len);
			if (in_array($char, $charArr)) {
				$str = mb_substr($str, 0, $len);
			} else {
				break;
			}
		}
		return $str;
	}
}