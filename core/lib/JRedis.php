<?php
namespace mt\core\lib;

/**
 * Class JRedis
 *
 * use single redis.php config file or add diy redis config to params.php like:
'redis' => [
    'default' => [
        'hostname' => '127.0.0.1', //ip or domain
        'port' => 6379,
        'database' => 0, //redis db index
        'timeout' => 3,
        'password' => 'yourPassword',
    ],
],
 *
 */
class JRedis
{
    private static $instance = [];

    /**
     * 获取 redis 连接
     * @to-do  断线重连 == 暂无需处理，redis client kill 后，系统会自动重连
     *
     * @param string $configName
     * @return \Redis|mixed
     * @throws \Exception
     */
    public static function getInstance($configName = 'default')
    {
        if(empty(self::$instance[$configName])){
            new self($configName);
        } else {
            $tr = self::$instance[$configName];
            if ('+PONG' != $tr->ping()) {
                self::$instance[$configName] = null;
                new self($configName);
            }
        }
        return self::$instance[$configName];
    }

    /**
     * JRedis constructor.
     * @param string $configName
     * @throws \Exception
     */
    private function __construct($configName = 'default')
    {
        if (!empty(self::$instance[$configName])) {
            return self::$instance[$configName];
        }
        $config = JConfig::getEnv('redis', $configName);
        $redis = new \Redis();
        $timeOut = isset($config['timeout']) ? intval($config['timeout']) : 3;
        $dbIndex = isset($config['database']) ? intval($config['database']) : 0;
        if ($redis->connect($config['hostname'], $config['port'], $timeOut)) {
            if (isset($config['password']) && $config['password']) {
                $redis->auth($config['password']);
            }
            $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            $redis->select($dbIndex);
            self::$instance[$configName] = $redis;
        } else {
            throw new \Exception("redis connect failed.");
        }
        return $redis;
    }

}
