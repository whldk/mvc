<?php

define('AGENT', 'http');

//init 配置
require_once __DIR__ . '/init.php';

//autoload  自动加载
require_once __DIR__ . '/autoload.php';

//config  加载要初始化的实例对象
$config = require_once DIR_APP . '/config/config.php';

//app 生成容器
$app = \App::getInstance($config);

//app 实例化所需要的组件  运行 并   返回结果
return $app->bootstrap();