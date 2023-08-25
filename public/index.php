<?php

namespace think;

// 调试模式开关
define('APP_DEBUG', false);
// 是否强制路由
define('URL_ROUTE_MUST',true);
// 定义CMF根目录,可更改此目录
define('CMF_ROOT', dirname(__DIR__) . '/');
// 定义CMF数据目录,可更改此目录
define('CMF_DATA', CMF_ROOT . 'data/');
// 定义下载文件存放目录
define('DOWN_PATH', CMF_ROOT . 'downloads/');
define('DOWN_PATH1', CMF_ROOT . 'public/downloads/');
define('TICKET_DOWN_PATH', DOWN_PATH . 'ticket/');
define('SUPPORT_DOWN_PATH', DOWN_PATH1 . 'support/');
define('DATABASE_DOWN_PATH', DOWN_PATH . 'database/');
// 定义文件上传目录
define('UPLOAD_PATH',CMF_ROOT . 'uploads/');
// 定义文件默认上传目录
define('UPLOAD_DEFAULT',UPLOAD_PATH . 'common/default/');
// 定义应用目录
define('APP_PATH', CMF_ROOT . 'app/');
// 定义网站入口目录
define('WEB_ROOT', __DIR__ . '/');
// 加载基础文件
require CMF_ROOT . 'vendor/thinkphp/base.php';
// 执行应用并响应
Container::get('app', [APP_PATH])->run()->send();