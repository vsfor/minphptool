<?php
namespace mt\core\lib;

class JLog
{
    /**
     * 记录日志信息 dir 为相对日志目录的路径，如 form 或 model/user
     *
     * @param $msg
     * @param string $dir
     * @return bool
     */
    public static function log($msg, $dir = '')
    {
        $path = appRuntimePath('logs/'.$dir);
        dirExistOrMake($path);
        $file = $path .DS. date("Y-m-d") . '.log';
        fileExistOrTouch($file);
        if(!is_string($msg) && !is_numeric($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }
        $msg = getMicroDate() . ' | ' . $msg . PHP_EOL;
        return error_log($msg, 3, $file);
    }

    public static function debug($msg, $dir = '')
    {
        if (J_DEBUG) {
            $dir = ($dir ? 'debug/'.$dir : 'debug');
            if(!is_string($msg) && !is_numeric($msg)) {
                $msg = json_encode($msg, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
            }
            return self::log($msg, $dir);
        }
        return true;
    }

    public static function exception(\Exception $e, $data = [],$dir='')
    {
        $dir = ($dir ? 'exception/'.$dir : 'exception');
        if(!is_string($data) && !is_numeric($data)) {
            $data = json_encode($data,JSON_UNESCAPED_UNICODE);
        }
        $msg = $data . PHP_EOL;
        $msg .= $e->getFile() .':'. $e->getLine() . PHP_EOL;
        $msg .= $e->getMessage() . PHP_EOL;
        $msg .= $e->getTraceAsString() . PHP_EOL;
        return self::log($msg, $dir);
    }

}