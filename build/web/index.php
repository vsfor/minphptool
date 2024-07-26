<?php
/*
php -d phar.readonly=0 ./mpt -r phar/pack
 */
try {
    defined('J_DEBUG') or define('J_DEBUG', true);//是否调试模式
    defined('J_ENV') or define('J_ENV', 'demo'); //环境｜主机｜项目

    Phar::loadPhar(dirname(__DIR__) .'/mpt.phar','mpt');
    require_once 'phar://mpt/web/index.php';
} catch (\Throwable $e) {
    echo $e->getMessage();
}