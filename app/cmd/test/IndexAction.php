<?php
namespace mt\app\cmd\test;

use mt\core\db\MySQLQuery;
use mt\core\excel\SimpleXLSXGen;
use mt\core\lib\JCli;
use mt\core\lib\JConfig;
use mt\core\lib\JLog;
use mt\core\lib\JRedis;

class IndexAction
{
    public function run($params=[])
    {
        showLog($params['msg'] ?? 'hi~');
        showLog(getMicroDate());
        showLog(__FILE__.':'.__LINE__);
        showLog($_SERVER['argv']??'argv is null');
        showLog($params);
        showLog(get_class_methods($this));
        $fun = JCli::prompt('>input the test fun name:',['required' => true]);
        if (method_exists($this, $fun)) {
            $this->{$fun}($params);
        } else {
            showLog($fun . ' not found.');
            return 1;
        }
        return 0;
    }

    /**
     * 路由检测， 主要用于 php-fpm http 路由解析校验，过滤非法请求
     * 允许 英文字母 + 数字 + / + . + 中划线 + 下划线
     * @return void
     */
    protected function checkRoute()
    {
        $str = 'a_b/c2;e\\/-.d/k,-e';
        $str = 'a_b/c2e/-d/k-e';
        $t = preg_match('/^([a-zA-Z0-9\/\.\-\_]+)$/',$str);
        showLog($t);
    }

    protected function forkJob()
    {
        showLog(getMicroDate());
        showLog('current pid:'.getPid());
        $pids = [];
        for($i = 0; $i<10; $i++) {
            $pid = pcntl_fork();//fork出子进程
            if ($pid == 0) {
                showLog($i . ' 子进程 '.getPid());
                exit();
            } else if ($pid > 0) {
                $pids[] = $pid;
            } else {
                showLog('fork error');
            }
        }

        foreach ($pids as $pid) {
            $status = null;
            pcntl_waitpid($pid, $status);
            showLog($pid.' done:'.jsonEncode($status));
        }
        showLog('all done.');
    }

    protected function redis()
    {
        $config = JConfig::getEnv('redis','default','hostname');
        showLog($config);
        JLog::log($config,'/some/path/');
        JRedis::getInstance()->set('a','b');
        $redis = JRedis::getInstance();
        $a = $redis->keys('*');
        showLog($a);
    }

    protected function excel()
    {
        $list = MySQLQuery::find('user')->groupBy(['email'])->all();
        showLog($list);

        if (!empty($list)) {
            $title = array_keys($list[0]);
            array_unshift($list,$title);
            $xlsx = SimpleXLSXGen::fromArray($list);
            $path = appRuntimePath('fileIO');
            dirExistOrMake($path);
            $file = getUniqueId().'.xlsx';
            if ($xlsx->saveAs($path.'/'.$file)) {
                showLog($path.'/'.$file);
            }
        }
    }

}