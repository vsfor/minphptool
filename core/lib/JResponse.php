<?php
namespace mt\core\lib;

/**
 * 响应报文信息，常用于web交互模式
 */
class JResponse
{
    /**
     * @param array|mixed $data
     * @return string
     */
    public static function json($data)
    {
        header('Content-Type: application/json; charset=UTF-8');
        return jsonEncode($data);
    }

    public static function html($content)
    {
        header('Content-Type: text/html; charset=UTF-8');
        return htmlentities($content);
    }

    public static function raw($text)
    {
        header('Content-Type: text/plain; charset=UTF-8');
        return $text;
    }

}