<?php
namespace mt\core\db;

use mt\core\lib\JConfig;

/**
 * mysql pdo连接
 * 使用场景复杂时，可结合 参数：diy 区分逻辑流程
 * 避免使用同一个连接，导致事务或其他逻辑异常
 */
class MySQLConnection
{
    private static $instance = null;
    private $conn;

    private function __construct($config)
    {
        $options = [
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}",
        ];
        $this->conn = new \PDO($config['dsn'], $config['username'], $config['password'], $options);
        $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->conn->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        $this->conn->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        $this->conn->setAttribute(\PDO::ATTR_PERSISTENT,true);
        //解决"this is incompatible with sql_mode=only_full_group_by:42000"查询模式问题
        $this->conn->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
    }

    public function getConnection()
    {
        return $this->conn;
    }

    /**
     * 获取连接
     * 无读写分离逻辑，阿里云RDS解决了读写分离需求
     *
     * @param string $db configName
     * @param string $diy usageType
     * @param bool $refresh
     * @return \PDO
     */
    public static function getInstance($db='db', $diy = '', $refresh = false)
    {
        $uniKey = self::getUniqueKey($db, $diy);
        if ($refresh) {
            self::$instance[$uniKey] = null;
        }

        if (empty(self::$instance[$uniKey]->conn)) {
            $config = JConfig::getEnv('mysql',$db);
            self::$instance[$uniKey] = (new self($config));
        }

        return self::$instance[$uniKey];
    }

    /**
     * 主动释放连接
     * @param string $db configName
     * @param string $diy usageType
     * @return void
     */
    public static function release($db='db',$diy='')
    {
        $uniKey = self::getUniqueKey($db,$diy);
        self::$instance[$uniKey] = null;
    }

    /**
     * 单例唯一标识
     * 一个进程 一个类型 创建一个连接
     * @param string $db configName
     * @param string $diy usageType
     * @return string
     */
    private static function getUniqueKey($db,$diy)
    {
        $pid = getPid();
        return md5("PID:{$pid}:DB:{$db}:DIY:{$diy}");
    }
}