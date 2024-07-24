<?php
/**
 * 一些需要 composer update 或 require 的依赖
 * 使用自定义的方式进行加载
 *
 * 项目维护一段时间后
 * 执行 composer update 更新依赖
 * 或 composer require 安装依赖
 * 操作不当会导致所有依赖全部更新，存在较大风险
 *
 * 当需要安装的包没有外部依赖，
 * 可参考此方式 注入需要加载的类或命名空间
 */

defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
defined('DS') or define('DS', DIRECTORY_SEPARATOR);// 目录分隔符
defined('PS') or define('PS', PATH_SEPARATOR);// 路径分隔符
defined('J_DEBUG') or define('J_DEBUG', true);//是否调试模式
defined('J_ENV') or define('J_ENV', 'local'); //环境｜主机｜项目

require_once 'functions.php';
if (appIsDebug()) {
    error_reporting(E_ALL);
}
function mt_autoload($className) {

    $classMap = [ // 类 => 文件  ， 命名空间 => 目录  注意尾部的 \\ 与 /
        'mt\\' => BASE_PATH . '/',
        '' => BASE_PATH . '/extend/', //注意规范目录命名
    ];

    if (isset($classMap[$className]) && is_file($classMap[$className])) {
        include $classMap[$className];
        return;
    }

    if (strpos($className, '\\') !== false) {
        foreach ($classMap as $namespace => $alias) {
            if (is_file($alias)) {
                continue;
            }

            if (empty($namespace)) {
                $path =  $alias . $className;
                $file = str_replace('\\','/',$path) . '.php';
                if (is_file($file)) {
                    include $file;
                    return;
                }
            }
            elseif (strpos($className, $namespace) !== false) {
                $path = str_replace($namespace, $alias, $className);
                $file = str_replace('\\','/',$path) . '.php';
                if (is_file($file)) {
                    include $file;
                    return;
                }
            }
        }
    }

}

spl_autoload_register('mt_autoload', true, false);
