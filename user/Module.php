<?php
/**
 * 加载user模块 自动继承父级配置
 */
namespace user;

use vendor\base\BaseModule;

final class Module extends BaseModule
{
	public static function getModuleName($moduleRoute = null)
	{
		return __NAMESPACE__;
	}
}