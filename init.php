<?php
//default_charset 设置utf-8格式
ini_set('default_charset', 'UTF-8');

//timezone 设置时钟
date_default_timezone_set('Asia/Shanghai');

//app name 设置项目名称
define('APP_NAME', 'mvc');

//gc_session的 存活时间
define('APP_TIMEOUT', 7200);

// N秒后不操作则自动退出
define('APP_AUTH_TIMEOUT', 3600);

//env 设置开发环境
define('ENV', 'dev');	//define('ENV', 'prod');

//env
define('ENV_SIDE', 'backend');	//define('ENV_SIDE', 'frontend');

//debug 是否开启debug 模式 线上运行时 false
define('DEBUG', true);

//strace frames limit  调试追踪级别
define('STRACE_LIMIT', 3);

//设置目录权限
define('DIR_MODE', 0755);

//设置文件权限
define('FILE_MODE', 0644);

//app dir 应用目录
define('DIR_APP', __DIR__);

// 日志目录
define('DIR_LOG', __DIR__ . DIRECTORY_SEPARATOR . 'log');

//静态文件目录
define('DIR_STATIC', __DIR__ . DIRECTORY_SEPARATOR . 'static');

//log file size 日志文件大小，超出会自动清理
define('LOG_FILE_SIZE', 5242880); //5M

//登录密码是否加密验证
define('LOGIN_HASH', false);

//error handling 屏蔽错误级别
if (defined('DEBUG') && DEBUG === true) {
	error_reporting(E_ALL);
} else {
	error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & E_STRICT);
}

if (defined('AGENT') && AGENT === 'http' && defined('ENV') && ENV === 'dev') {
	ini_set('display_errors', '1');
} else {
	if (!defined('AGENT')) {
		define('AGENT', 'app');
	}
	ini_set('display_errors', 0);
	ini_set('log_errors', 1);
	ini_set('ignore_repeated_errors', 1);
	$log_dir = defined('DIR_LOG') ? DIR_LOG : __DIR__ . DIRECTORY_SEPARATOR . 'log';
	if (!is_dir($log_dir) && mkdir($log_dir, DIR_MODE, true) === false) {
		error_log('Can\'t create log dir');
	} else {
		$log_file = $log_dir . DIRECTORY_SEPARATOR . AGENT . '_error.log';
		if (!is_file($log_file)) {
			touch($log_file);
		}
		ini_set('error_log', $log_file);
	}
}


/**
 * db 数据库配置
 */
$db = [
    'dev' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=mvc;charset=UTF8',
        'username' => 'root',
        'password' => '123456'
    ],
    'prod' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=mvc;charset=UTF8',
        'username' => 'root',
        'password' => '123456'
    ]
];

/**
 * 配置多个日志目录名字, 可以根据不同模块来指定存放
 * 默认配置无模块的db记录
 */

$log_file = [
    'db' => [
        'enable' => true,
        'file' =>  DIR_LOG . DIRECTORY_SEPARATOR . 'db.log'
    ]
];