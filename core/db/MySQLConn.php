<?php
namespace mt\core\db;

use mt\core\lib\JConfig;

/**
 * mysql pdo连接单例
 */
class MySQLConn
{
    private static $instance;
    private function __construct() {}
    private function __clone() {}

    /**
     * 获取连接
     * 无读写分离逻辑，阿里云RDS解决了读写分离需求
     *
     * @param string|mixed $db configName
     * @param string|mixed $diy usageType
     * @param bool|mixed $refresh
     * @return \PDO|mixed
     */
    public static function getInstance($db='db', $diy = '', $refresh=false)
    {
        if (empty(static::$instance) || $refresh) {
            $config = JConfig::getEnv('mysql',$db);
            $options = [
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}",
            ];
            static::$instance = new \PDO($config['dsn'], $config['username'], $config['password'], $options);
            static::$instance->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            static::$instance->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
            static::$instance->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            static::$instance->setAttribute(\PDO::ATTR_PERSISTENT,true);
            //解决"this is incompatible with sql_mode=only_full_group_by:42000"查询模式问题
            static::$instance->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
        }

        return static::$instance;
    }

}