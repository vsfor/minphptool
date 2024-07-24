<?php
namespace mt\core\lib;

class JHttp
{

    public static function httpGet($url, $safe = true, $timeout = 3) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);

        if ($safe) {
            // 为保证数据传输的安全性，采用https方式调用，必须使用下面2行代码打开ssl安全校验。
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        }

        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

    public static function httpPost($url, $body, $type='json', $safe = true, $timeout = 3)
    {
        if (!is_string($body)) {
            if ($type == 'json') {
                $body = json_encode($body);
            } else {
                $body = http_build_query($body);
            }
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);

        if ($safe) {
            // 为保证数据传输的安全性，采用https方式调用，必须使用下面2行代码打开ssl安全校验。
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        }

        if ($type == 'json') {
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                "Connection: keep-alive",
                "Content-Type: application/json; charset=UTF-8", //传送的数据类型
                "Content-Length: ".strlen($body) //传送数据长度
            ]);
        } else {
            curl_setopt($curl, CURLOPT_HTTPHEADER, [
                "Connection: keep-alive"
            ]);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $body);//要传送的所有数据

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

}