<?php

namespace App\Supports;

use App\Exceptions\Error;
use App\Mail\SendErrorMail;
use App\Models\FinanceFlowLogsModel;
use App\Repositories\EmergencyLogsRepository;
use App\Services\Api\CacheService;
use App\Services\Api\QueueService;
use App\Services\MailService;
use App\Supports\WxBizMsgCrypt\PrpcryptEncoder;
use App\Type\EmergencyLogType;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

class Helper
{

    /**
     * rsa 公钥加密
     * @param $text
     * @return string
     */
    public static function encryptByPublicKey($text): string
    {
        $text = urlencode($text);

        $publicKey = self::fetchPublicKey();
        $pu_key = openssl_pkey_get_public($publicKey);//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id

        $RSA_ENCRYPT_BLOCK_SIZE = 117;

        $result = '';
        $data = str_split($text, $RSA_ENCRYPT_BLOCK_SIZE);
        foreach ($data as $block) {
            openssl_public_encrypt($block, $dataEncrypt, $pu_key);
            $result .= base64_encode($dataEncrypt);
        }

        return $result;
    }

    /**
     * 获取私钥 rsa加解密用
     * @return false|string
     */
    private static function fetchPrivateKey()
    {
        return file_get_contents(dirname(app_path()) . '/certs/rsa_private_key.pem');
    }

    /**
     * rsa 私钥解密
     * @param $encrypted
     * @return string
     */
    public static function decryptByPrivateKey($encrypted): string
    {
        $privateKey = self::fetchPrivateKey();
        $pi_key = openssl_pkey_get_private($privateKey);//这个函数可用来判断私钥是否是可用的，可用返回资源id Resource id

        $result = '';

        $ex = '=';

        $data = explode($ex, $encrypted);
        $data = array_filter($data);

        foreach ($data as $block) {
            $block .= $ex;
            openssl_private_decrypt(base64_decode($block), $dataDecrypt, $pi_key);

            $result .= $dataDecrypt;
        }
        return urldecode($result);
    }

    /**
     * 获取公钥 rsa加解密用
     * @return false|string
     */
    public static function fetchPublicKey()
    {
        return file_get_contents(dirname(app_path()) . '/certs/rsa_public_key.pem');
    }

    /**
     * 打码
     * @param string $string
     * @return string
     */
    public static function mosaicString(string $string): string
    {
        return substr($string, 0, 2) . '***' . strrev(substr(strrev($string), 0, 2));
    }

    /**
     * 获取随机字符串
     * @param int $len
     * @param bool $complex
     * @return string
     */
    public static function randomString(int $len = 10, bool $complex = false): string
    {
        $str = 'aqwertyuiopasdfghjklzxcvbnm1234567890';

        if ($complex) {
            $str .= '.!@#QWERTYUOPASDFGHJKLZXCVBNM';
        }

        $random = '';

        for ($i = 0; $i < $len; $i++) {
            $random .= substr(str_shuffle($str), rand(0, strlen($str) - 1), 1);
        }

        return $random;
    }

    // 获取ip
    public static function getIp()
    {
        if (isset($_SERVER["HTTP_CLIENT_IP"]) && strcasecmp($_SERVER["HTTP_CLIENT_IP"], "unknown")) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        } else {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && strcasecmp($_SERVER["HTTP_X_FORWARDED_FOR"], "unknown")) {
                $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
            } else {
                if (isset($_SERVER["REMOTE_ADDR"]) && strcasecmp($_SERVER["REMOTE_ADDR"], "unknown")) {
                    $ip = $_SERVER["REMOTE_ADDR"];
                } else {
                    if (isset ($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] && strcasecmp($_SERVER['REMOTE_ADDR'],
                            "unknown")
                    ) {
                        $ip = $_SERVER['REMOTE_ADDR'];
                    } else {
                        $ip = "unknown";
                    }
                }
            }
        }
        return ($ip);
    }

    public static function isPhoneNumber(string $number): bool
    {
        // 正则表达式匹配手机号码
        // 以下正则表达式适用于匹配多种国家的手机号码格式
        // 中国大陆: 11位数字，以1开头
        // 美国: 10位数字，可能以1开头
        // 其他国家: 以1开头的10或11位数字
        $pattern = '/^1\d{10}$/';

        return preg_match($pattern, $number) > 0;
    }

    /**
     * 是否是正确的邮箱
     * @param string $email
     * @return bool
     */
    public static function isEmail(string $email): bool
    {
        return (bool)filter_var($email, FILTER_VALIDATE_EMAIL);
    }
}
