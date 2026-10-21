<?php

namespace App\Supports;

class Http
{
    public static function curl_post($url, $postdata, $header = [])
    {
        $postdata = json_encode($postdata, JSON_UNESCAPED_UNICODE);
        $_header = array(
            'Accept: application/json',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postdata),
            'User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727; .NET CLR 3.0.04506.648; .NET CLR 3.5.21022)'
        );

        $header = array_merge($header, $_header);

        logger()->debug(sprintf('curl_post request:%s',$url), [
            'postdata' => $postdata,
            'headers' => $header
        ]);

        //初始化
        $curl = curl_init();

        //设置抓取的url
        curl_setopt($curl, CURLOPT_URL, $url);
        //设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_HEADER, 0);
        //设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 超时设置
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);

        // 超时设置，以毫秒为单位
        // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);

        // 设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        //设置post方式提交
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
        //执行命令
        $data = curl_exec($curl);
//        $a = curl_getinfo($curl);
//        var_dump($a);
        // 显示错误信息
        if (curl_error($curl)) {
            throw new Exception("Error: " . curl_error($curl));
        } else {
            // 打印返回的内容
            curl_close($curl);
        }

        return $data;
    }
}
