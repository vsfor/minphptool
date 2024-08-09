<?php
namespace mt\core\lib;

/**
 * 不依赖 BCMath 扩展
 * 金额数值比对及处理  注意参数类型
 * 解决金额比对过程中 系统浮点型存储可能导致的比对异常问题
 */
class JMoney
{
    /**
     * 元转换为分
     * @param $num
     * @return int
     */
    public static function yuan2fen($num)
    {
        return (int) number_format($num, 2, '', '');
    }

    /**
     * 分转换为元 默认保留2位小数
     * @param $num
     * @param int $decimal
     * @return float
     */
    public static function fen2yuan($num, $decimal = 2)
    {
        return (float) number_format($num / 100, $decimal,'.','');
    }

    /**
     * a = b
     * @param $a
     * @param $b
     * @param int $decimal
     * @return bool
     */
    public static function aEqualToB($a,$b,$decimal=2)
    {
        $abs = abs($a - $b);
        $refer = pow(10, -5 - $decimal);
        return $abs < $refer;
    }

    /**
     * a > b
     * @param $a
     * @param $b
     * @param int $decimal
     * @return bool
     */
    public static function aGreaterThanB($a,$b,$decimal=2)
    {
        $diff = $a - $b;
        $refer = pow(10, -5 - $decimal);
        return $diff > $refer;
    }

    /**
     * a < b  冗余方法，方便代码语义理解
     * @param $a
     * @param $b
     * @param int $decimal
     * @return bool
     */
    public static function aSmallerThanB($a,$b,$decimal=2)
    {
        $diff = $b - $a;
        $refer = pow(10, -5 - $decimal);
        return $diff > $refer;
    }

    /**
     * 小数位 向上 保留取整
     * @param $num
     * @param int $decimal
     * @return float|int
     */
    public static function decimalCeil($num, $decimal=2)
    {
        $times = pow(10, $decimal);
        $temp = ceil($num * $times);
        $final = $temp / $times;
        return $final;
    }

    /**
     * 小数位 向下 保留取整
     * @param $num
     * @param int $decimal
     * @return float|int
     */
    public static function decimalFloor($num, $decimal=2)
    {
        $times = pow(10, $decimal);
        $temp = floor($num * $times);
        $final = $temp / $times;
        return $final;
    }

    /**
     * 小数位 系统默认(四舍五入)
     * @param $num
     * @param int $decimal
     * @return float
     */
    public static function decimalCut($num, $decimal=2)
    {
        $final = (float) number_format($num, $decimal, '.', '');
        return $final;
    }


}
