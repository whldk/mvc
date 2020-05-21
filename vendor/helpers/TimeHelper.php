<?php
namespace vendor\helpers;

class TimeHelper
{
	public static function lastMidnight()
	{
		return self::midnight() - 86400;
	}
	
	public static function midnight()
	{
		return strtotime(date('Y-n-j', time()));
	}
}