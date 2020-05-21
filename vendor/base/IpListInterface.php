<?php
namespace vendor\base;

Interface IpListInterface
{
	/**
	 * 待检测ip
	 * @param string $ip
	 * @return bool 是否在黑名单
	 */
	public function black($ip = null);
	
	/**
	 * 待检测ip
	 * @param string $ip
	 */
	public function white($ip = null);
}