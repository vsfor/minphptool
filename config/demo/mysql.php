<?php
/**
 * 数据库连接配置
 * 注意：
 *    不支持前缀，查询请使用完整表名
 *    默认兼容full group by 模式
 */
return [
    'db' => [
        'dsn' => 'mysql:host=127.0.0.1;dbname=test_mpt;port=3306',
        'username' => 'root',
        'password' => '123456',
        'charset' => 'utf8mb4',
    ],
];