<?php
/**
 * php-fpm 入口脚本
 */
//移除、伪装header信息
header_remove('X-Powered-By');
header('Server: IIS 8.5');
//初始化响应头
$protocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
$statusCode = 200;
$statusMsg = 'OK';
//允许跨域
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: *');
//项目初始化
defined('BATH_PATH') or define('BASE_PATH', dirname(__DIR__)); // 项目路径
defined('J_DEBUG') or define('J_DEBUG', true);//是否调试模式
defined('J_ENV') or define('J_ENV', 'local'); //环境｜主机｜项目
require BASE_PATH . '/core/autoload.php';
//解析路由
$route = trim($_SERVER['REQUEST_URI'] ?? '', '/');
if (strpos($route,'?') !== false) {
    $route = explode('?', $route)[0];
}
if (empty($route)) {
    $route = 'api/site/index';
}
if (!preg_match('/^([a-z0-9\/\-]+)$/', $route)) {
    $statusCode = 500;
    $statusMsg = 'routeErr';
    if (J_DEBUG) {
        $statusMsg = "invalidRoute:{$route}";
    }
    header("{$protocol} {$statusCode} {$statusMsg}");
    return 0;
}
$path = explode('/', $route, 3);
$pathLen = count($path);

$action = str_replace('/','-',$path[$pathLen-1] ?? 'index');
$controller = $path[$pathLen-2] ?? 'site';
$module = $path[$pathLen-3] ?? 'api';

$actionStr1 = ucwords(implode(' ', explode('-', $action)));
$actionStr2 = str_replace(' ', '', $actionStr1);
$actionClass = $actionStr2.'Action';
$class = "\\mt\\app\\{$module}\\{$controller}\\{$actionClass}";
//执行业务逻辑
if (class_exists($class)) {
    try {
        $actionObj = new $class();
        $actionObj->run();
    } catch (\Throwable $e) {
        $statusCode = 500;
        $statusMsg = 'ServerError';
        if (J_DEBUG) {
            showLog("\n<br/>Exception: ");
            showLog($e->getFile().':'.$e->getLine());
            showLog($e->getMessage());
            showLog($e->getTraceAsString());
        }
    }
} else {
    $statusCode = 404;
    $statusMsg = 'NotFound';
    if (J_DEBUG) {
        showLog('route error: class '.$class.' not found.');
    }
}
header("{$protocol} {$statusCode} {$statusMsg}");
return 0;