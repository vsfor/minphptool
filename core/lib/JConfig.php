<?php
namespace mt\core\lib;

/**
 * 项目相关动态配置
 *
 * 目前使用文件配置，每次实时读取。后续可考虑使用 Yaconf 或 Redis 缓存。
 */
class JConfig
{
    /**
     * 获取环境配置信息， 可传递多个参数， 至少一个 依次代表fileName  key  subKey  subSubKey ...
     * @return array
     */
    public static function getEnv()
    {
        $path = func_get_args() ? : ['params'];
        $file = appConfigPath(J_ENV) .'/'.$path[0].'.php';
        if (!file_exists($file)) {
            return null;
        }
        $config = (require $file);
        $i = 1;
        while (isset($path[$i])) {
            if (isset($config[$path[$i]])) {
                $config = $config[$path[$i]];
                $i++;
                continue;
            }
            return null;
        }

        return $config;
    }

}