<?php


namespace App\Supports;


class Code
{
    const SUCCESS = 200; // 成功
    const ERROR = 400; // 失败

    const UNAUTHORIZED = 401;
    const LOGIN_FAILED = 402;
    const LOGIN_EXPIRED = 403;
    const PASSWD_ERROR = 409;
    const UNAUTHORIZED_FORCE_LOGIN = 444;

    const RESOURCE_NOT_FOUND = 404;
    const QUERY_FAILED = 406;
    const OPERATE_FAILED = 407;
    const UPLOAD_FAILED = 408;
    const FREQUENT_OPERATION = 410;
    const OUT_OF_LIMIT = 411;

    const SYSTEM_FAILED = 500;
    const HTTP_FAILED = 501;
    const REQUEST_PARAM_ERROR = 1000;
    const STATUS_NOT_MATCH = 1001;
    const ACTION_NOT_MATCH = 1002;

    const USER_ACCOUNT_EXIST = 4007;
    const USER_ACCOUNT_NOT_MATH = 4008;

    const MSG_MAP = [
        self::SUCCESS => '成功',
        self::ERROR => '操作失败',

        self::UNAUTHORIZED => '请登录',
        self::LOGIN_FAILED => '用户不存在或密码错误',
        self::LOGIN_EXPIRED => '登录状态已过期，请重新登录',
        self::PASSWD_ERROR => '密码错误',
        self::UNAUTHORIZED_FORCE_LOGIN => '请登录',
        self::RESOURCE_NOT_FOUND => '未查询到相关资源',
        self::QUERY_FAILED => '查询失败',
        self::OPERATE_FAILED => '操作失败',
        self::UPLOAD_FAILED => '上传失败',
        self::FREQUENT_OPERATION => '操作频繁，请稍后重试',
        self::OUT_OF_LIMIT => '超出限额',
        self::SYSTEM_FAILED => '系统繁忙',
        self::HTTP_FAILED => '第三方接口请求失败',
        self::REQUEST_PARAM_ERROR => '请求参数有误',
        self::STATUS_NOT_MATCH => '状态不正确',
        self::ACTION_NOT_MATCH => '类型不正确',

        self::USER_ACCOUNT_EXIST    => '账号已存在',
        self::USER_ACCOUNT_NOT_MATH    => '账号无权限',
    ];
}
