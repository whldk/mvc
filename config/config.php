<?php
/**
 * 注册配置实例对象
 */
return [
	'access' => [
	    '*' => []           //允许方法的权限
    ],
	'components' => [       //要加载的组件
		'db',               //db组件
        'session',          //session管理
        'user',             //用户权限
        'route',            //路由管理
        'request',          //请求组件
        'response',         //响应组件
        'upload',           //上传文件组件
        'logger',           //日志记录组件
        'HtmlPurifier',     //html文本过滤组件
        'security',         //安全组件
        'ipWall'            //防火墙过滤组件
	],
	'db' => [       //对应的db组件初始化  配置
		'class' => '\vendor\db\Db',
		'dsn' => $db[ENV]['dsn'] ?? '',
		'username' => $db[ENV]['username'] ?? '',
		'password' => $db[ENV]['password'] ?? '',
		'logger' => [
				'enable_log' => $log_file['db']['enable'] ?? false,
				'logFile' => $log_file['db']['file'] ?? ''
		],
		'defaultCallbackOnError' => function () {
			throw new vendor\exceptions\DbErrorException();
		}
	],      //session 配置
	'session' => [
		'class' => '\vendor\base\DbSession',    //初始化db session
		'name' => APP_NAME,          //根据当前应用 生成cookie的名称
		'sessionTable' => 'session', //指定存放在database中的表名
		'timeout' => APP_TIMEOUT,          //指定session的过期时间,会自动清理
	],
	'user' => [
		'enableAutoLogin' => true,
		'identityCookie' => 'identity_' . APP_NAME,
		'cookieLifetime' => 2592000,	//30 * 24 * 60 * 60  记住密码的 cookie 存活时间
		'identityClass' => 'user\models\IdentityModel',    //初始化的对象
		'enableExtraCookies' => true        //启用额外的cookie配置
	],
	'security' => [
		'class' => '\vendor\base\Security'
	],
	'route' => [
			'default_action' => 'index',        //设置默认路由
	],
	'request' => [              //开启请求身份验证
		'enableCookieValidation' => true,
		'cookieValidationKey' => 'kjiefJNKK:_(*&^%@!&_{+?:!',
		'validateCookies' => [APP_NAME]
	],
    'HtmlPurifier' => [
        'class' => 'HTMLPurifier'
    ],
	'ipWall' => [
			'ipList' => '\models\IpListModel'
	],
];
