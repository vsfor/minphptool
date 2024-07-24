<?php

function isCli() {
    return PHP_SAPI === 'cli';
}

function d($obj) {
    if(isCli()) {
        $br = PHP_EOL;
    } else {
        $br = '<br>';
    }
    if (is_bool($obj) || is_null($obj)) {
        var_dump($obj);
    } elseif (is_string($obj) && $br=='<br>') {
        echo htmlentities($obj);
    } else {
        if($br == '<br>') {
            echo '<pre>';
            print_r($obj);
            echo '</pre>';
        } else {
            print_r($obj);
        }
    }
    echo $br;
}
function dd($s) {
    d($s);
    die();
}
function showLog($mixed,$dump=0) {
    if ($dump) {
        var_dump($mixed);
    } else {
        d($mixed);
    }
}

function jsonEncode($data, $flags = 0) {
    return json_encode($data, JSON_UNESCAPED_UNICODE | $flags);
}

function jsonDecode($json, $asArray = true) {
    return json_decode((string) $json, $asArray);
}

function getMicroDate($format='Y-m-d H:i:s.v') {
    $retry = 0;
    do {
        if ($retry > 0) { //date_create_from_format 可能返回false
            usleep(5000);
        }
        $dt = date_create_from_format('U.u', $microTimeFloat ?? microtime(true));
        $retry++;
    } while($dt === false);
    return $dt->setTimezone(new \DateTimeZone('Asia/Shanghai'))->format($format);
}

//文件初始化
function fileExistOrTouch($file, $mode = 0777) {
    if (is_file($file)) return true;
    dirExistOrMake(dirname($file));
    \touch($file);
    \chmod($file, $mode);
    return true;
}

//目录初始化
function dirExistOrMake($path, $mode = 0777) {
    if (is_dir($path)) return true;
    dirExistOrMake(dirname($path), $mode);
    \mkdir($path, $mode);
    return true;
}

//获取进程ID
function getPid() {
    try {
        if (\DIRECTORY_SEPARATOR === '\\') {
            return \getmypid(); //for windows
        }
        return \posix_getpid();
    } catch (\Throwable $e) {
        return 0;
    }
}

//文件大小格式化
function formatBytes(int $input,int $dec = 2):string {
    $prefix_arr = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $value = round($input, $dec);
    $i = 0;
    while ($value > 1024) {
        $value /= 1024;
        $i++;
    }

    return round($value, $dec).$prefix_arr[$i];
}

//计算耗时 ms
function getSpendMS(float $start,float $now=null):int {
    if (is_null($now)) {
        $now = microtime(true);
    }
    return (int) ceil(($now - $start) * 1000);
}

function getUniqueId() {
    return md5(uniqid().':'.microtime(true).':'.mt_rand(100000,999999));
}

function appConfigPath($dir=null) {
    return appBasePath() .'/config'.($dir?'/'.$dir:'');
}

function appRuntimePath($dir=null) {
    return appBasePath() .'/runtime'.($dir?'/'.$dir:'');
}

function appBasePath() {
    if (\Phar::running()) {
        return pathinfo(\Phar::running(false), PATHINFO_DIRNAME);
    }
    return BASE_PATH;
}

function appIsDebug() {
    if (J_DEBUG) {
        return true;
    }
    $env = strtolower(J_ENV);
    return in_array($env,['debug','local','test','dev','develop','development','alpha']);
}