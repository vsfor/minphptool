<?php
namespace mt\core\excel;

use mt\core\yiihelper\YiiArrayHelper;

/**
 * 依赖 xlswriter 扩展
 * @see https://pecl.php.net/package/xlswriter
 * 注意： 只支持xlsx,csv类型的表格文件
 * 安装配置添加 --enable-reader
 *
 * 新版本可能会出现文件损坏 无法打开的情况： 表格列数不要太多、内容建议过滤表情符号、及时释放文件资源
 */
class VtifulExcel
{
    /**
     * 将查询结果存储为 xlsx文件
     * @param array $rows
     * @param array $attributeMap  attribute => label
     * @return array|bool
     */
    public static function rows2Xlsx(array $rows, $attributeMap)
    {
        $path = appRuntimePath('fileIO/temp');
        dirExistOrMake($path);
        $excel  = new \Vtiful\Kernel\Excel(['path' => $path]);

        $fileName = date('ymdHis_') . uniqid() . '.xlsx';
        $object = $excel->constMemory($fileName);

        $header = array_values($attributeMap);
        $object->header(array_values($header));
        $object->freezePanes(1,0);

        $attributes = array_keys($attributeMap);
        $_rowIndex = 1;
        foreach ($rows as $row) {
            if (!$row) continue; //空行跳过
            foreach ($attributes as $col => $attr) {
                $_val = strval($row[$attr] ?? '');
                if (is_numeric($_val) && $_val < 999999999) {
                    $_val = doubleval($_val);
                }
                $object->insertText($_rowIndex, $col, $_val);
            }
            $_rowIndex += 1;
        }

        $file = $object->output();

        if (file_exists($file)) {
            return $file;
        }
        return false;
    }

    /**
     * 存储为 excel文件
     * 超过1000行，则使用固定内存模式
     *
     * @param array $header
     * @param array $rows //数据行 建议不指定键名 并将长整型 转换为字符串
     * @param bool $hasKey //明确指定是否存在键名  效率较高
     * @param string $dir
     * @param bool $freezeHeader
     * @param bool $webRoot  //路径设置
     * @return bool|string
     * @throws \Exception
     */
    public static function saveSheet($header=[],$rows=[],$hasKey=true,$dir='',$freezeHeader = true)
    {
        $path = appRuntimePath($dir?:'fileIO');
        dirExistOrMake($path);
        $excel  = new \Vtiful\Kernel\Excel(['path' => $path]);

        $fileName = date('ymdHi_') . uniqid() . '.xlsx';
        if (count($rows) > 1000) {
            $result = $excel->constMemory($fileName);
        } else {
            $result = $excel->fileName($fileName);
        }
        if ($header) {
            $result->header($header);
            if ($freezeHeader) {
                $result->freezePanes(1, 0);
            }
        }
        try {
            if ($hasKey) {
                $final = [];
                foreach ($rows as $row) {
                    $final[] = array_values($row);
                }
                $file = $result->data($final)->output();
            } else {
                $file = $result->data($rows)->output();
            }
            unset($result);
            unset($excel);
            if (file_exists($file)) {
                return $file;
            }
        } catch (\Exception $e) {
            return false;
        }

        return false;
    }


    /**
     * 读取excel文件内容
     * 注意只支持 xlsx 格式
     *
     * @param string $path 文件路径
     * @param int $index 读取的表单序号 默认为0(第一张表)
     * @param array $colType 列类型  0为第一列   1=String 2=Int  4=Double  8=TimeStamp
     * @param bool $returnCur
     * @return array|bool
     */
    public static function readSheet($path, $index=0, $colType=[], $returnCur=false)
    {
        if (!file_exists($path)) {
            return false;
        }
        $dir = dirname($path);
        $file = substr($path, strlen($dir) + 1);
        $excel = new \Vtiful\Kernel\Excel([
            'path' => $dir,
        ]);

        $typeConfig = $colType ? : array_fill(0, 64, 1);
        $excel = $excel->openFile($file);
        $sheetName = null;
        $sheetList = $excel->sheetList();
        if (isset($sheetList[$index])) {
            $sheetName = $sheetList[$index];
        } else {
            unset($sheetList);
            unset($excel);
            return [];
        }
        unset($sheetList);
        $excel = $excel->openSheet($sheetName,\Vtiful\Kernel\Excel::SKIP_EMPTY_ROW);
        if ($returnCur) {
            return $excel->setType($typeConfig);
        }
        $data = $excel->setType($typeConfig)->getSheetData();
        unset($excel);
        return $data;
    }


    /**
     * 保存为excel文件  使用固定内存模式
     *
     * 单表最大行数建议不超过 65535 ，此处默认每页最多存储5万条记录
     *
     * eg:
    $data = [
    'title' => '用户信息', //xls 表名
    'map' => [ //col = line列名   key = line数据key
    ['col' => '用户ID', 'key' => 'user_id'],
    ['col' => '用户昵称', 'key' => 'nickname'],
    ],
    'rows' => [ //line:  key => value
    ['user_id' => 123, 'nickname' => '张三', 'gender' => '男'],
    ['user_id' => 195, 'nickname' => 'Lily', 'gender' => '女'],
    ],
    ];
     *
     * @param array $data
     * @param int $pageSize
     * @param string $dir
     * @param bool $webRoot web文件加路径
     * @return bool|string
     * @throws \Exception
     */
    public static function saveAsExcel($data, $pageSize = 50000, $dir='')
    {
        $path = appRuntimePath($dir?:'fileIO');
        dirExistOrMake($path);
        $excel  = new \Vtiful\Kernel\Excel(['path' => $path]);

        $fileName = date('ymdHi_') . uniqid() . '.xlsx';
        $object = $excel->constMemory($fileName);

        $total = count($data['rows']);
        $page = ceil($total/$pageSize);
        $header = YiiArrayHelper::getColumn($data['map'], 'col');
        for ($sheetIndex = 0; $sheetIndex < $page; $sheetIndex++) {
            if ($sheetIndex > 0) {
                $object->addSheet('Sheet'.($sheetIndex + 1));
            }
            $rows = array_slice($data['rows'], $sheetIndex * $pageSize, $pageSize);

            $object->header($header);
            $object->freezePanes(1,0);
            $_rowIndex = 1;
            //表格数据的输出
            foreach ($rows as $row) {
                if (!$row) continue; //空行跳过
                foreach ($data['map'] as $col => $item) {
                    $_val = strval($row[$item['key']] ?? '');
                    if (is_numeric($_val) && $_val < 999999999) {
                        $_val = doubleval($_val);
                    }
                    $object->insertText($_rowIndex, $col, $_val);
                }
                $_rowIndex += 1;
            }
        }

        $file = $object->output();
        unset($object);
        unset($excel);
        if (file_exists($file)) {
            return $file;
        }
        return false;
    }

    /**
     * @param mixed $data filePath | dataArray | excel cursor
     * @param int $cols
     * @param int $offset
     * @param bool $must
     * @param array $format
     * @return array
     */
    public static function getHandledData($data,$cols=10,$offset=1,$must=false,$format=[])
    {
        if (is_string($data)) {
            $data = static::readSheet($data, 0, [], true);
        }
        $isObj = is_object($data);
        if (!$isObj && !is_array($data)) {
            return [];
        }
        $final = [];
        if ($must && $must >= $cols) $must=false;
        $line = -1;
        do {
            if ($isObj) {
                $row = $data->nextRow();
            } else {
                $row = array_shift($data);
            }
            $line++;
            if ($line < $offset) continue;

            $t = [];
            for ($i = 0; $i < $cols; $i++) {
                if (isset($row[$i])) {
                    if (isset($format[$i])) {
                        $t[$i] = static::handleColFormat($row[$i], $format[$i]);
                    } else {
                        $t[$i] = trim(strval($row[$i]));
                    }
                } else {
                    $t[$i] = '';
                }
            }

            if (is_array($row)
                && implode('',$t)
                && ($must===false || $t[$must])
            ) {
                $final[] = $t;
            }
        } while($row && $line<1000000);

        unset($data);
        return $final;
    }

    /**
     * 表格列格式化规则配置 列索引从0开始
     * [
     *      0 => 'date:[<dateFormat>]', //dateFormat like Y-m-d, default:Y-m-d H:i:s
     * ]
     * @param string $value
     * @param mixed $format
     * @return mixed
     */
    public static function handleColFormat($value,$format)
    {
        if (is_callable($format)) {
            return call_user_func_array($format, [$value]);
        } elseif (strncmp($format,'date:',5) == 0 && is_numeric($value)) {
            $dateA = explode(':', $format,2);
            $dateFormat = empty($dateA[1]) ? 'Y-m-d H:i:s' : $dateA[1];
            return static::dateExcelToPHP($value, $dateFormat);
        }
        return $value;
    }

    /**
     * excel 日期列转换
     * @param int $dateValue
     * @param string $format
     * @param string $timezone   UTC  + -  0-12
     * @param bool $base1900
     * @return float|int
     */
    public static function dateExcelToPHP($dateValue = 0, $format='Y-m-d H:i:s', $timezone='UTC+8', $base1900 = true)
    {
        if (!is_numeric($dateValue)) return $dateValue;
        if ($dateValue > 999999) return $dateValue;

        if ($base1900) {
            $my_excelBaseDate = 25569;
            //	Adjust for the spurious 29-Feb-1900 (Day 60)
            if ($dateValue < 60) {
                --$my_excelBaseDate;
            }
        } else {
            $my_excelBaseDate = 24107;
        }

        if ($dateValue >= 1) {
            $utcDays = $dateValue - $my_excelBaseDate;
            $time = round($utcDays * 86400);
        } else {
            $hours = round($dateValue * 24);
            $mins = round($dateValue * 1440) - round($hours * 60);
            $secs = round($dateValue * 86400) - round($hours * 3600) - round($mins * 60);
            $time = (integer) gmmktime($hours, $mins, $secs);
        }

        $timezoneHour = 0 - intval(str_replace('UTC','',$timezone));
        $timezoneSecond = $timezoneHour * 3600;
        $final = (integer) ($time + $timezoneSecond);

        return date($format, $final);
    }


}