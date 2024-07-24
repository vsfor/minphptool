<?php
namespace mt\app\cmd\app;


/**
 * 项目初始化:
 *  设置运行时目录权限
 */
class InitAction
{
    public function run()
    {
        try {
            exec('chmod -R 0777 '.appRuntimePath());
        } catch (\Throwable $e) {
            showLog($e->getMessage());
        }
        return 0;
    }

}