<?php

/**
 *  运行脚本任务部署并且按计划启动
 */

use vendor\base\ConsoleRequest;
use vendor\base\ConsoleResponse;
use vendor\base\ConsoleUser;

define('AGENT', 'script');

set_time_limit(600);

//init
require_once __DIR__ . '/../init.php';

//autoload
require_once __DIR__ . '/../autoload.php';

//constants
require_once __DIR__ . '/../config/constants.php';

//config
$config = require __DIR__ . '/../config/config.php';

$config['request'] = new ConsoleRequest();
$config['response'] = new ConsoleResponse();
$config['user'] = new ConsoleUser();

//取消apiLog组件
unset($config['apiLog']);
$config['components'] = array_diff($config['components'], ['apiLog']);

//app
$app = \App::getInstance($config);

/* @var $app \App */
return $app->bootstrap();