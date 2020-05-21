<?php
/**
 * 富文本编辑 权限和安全验证过滤
 */
namespace ueditor;

use vendor\base\BaseModule;

final class Module extends BaseModule
{
	public static function getModuleName($moduleRoute = null)
	{
		return __NAMESPACE__;
	}
}