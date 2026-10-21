<?php

namespace App\Exceptions;

use App\Supports\Code;
use Illuminate\Http\JsonResponse;
use Throwable;

class Error extends \Exception
{
    /**
     * 只显示配置好的异常直接抛出的异常信息，其他异常返回统一报错
     * @var string[]
     */
    protected static $reports = [
        Error::class,
    ];

    public function __construct($code = 0, $message = '', Throwable $previous = null)
    {

        if(empty($message) && isset(Code::MSG_MAP[$code]) && $code != Code::SUCCESS) {
            $message = Code::MSG_MAP[$code];
        }
        parent::__construct($message, $code, $previous);
    }

    // 处理异常
    public static function processException($exception): array
    {
        $data = [
            'code'  =>  Code::ERROR,
            'errorMsg'  =>  '系统繁忙',
        ];

        if (in_array(get_class($exception), self::$reports)) {
            $data['code'] = $exception->getCode();
            $data['errorMsg'] = $exception->getMessage() ? $exception->getMessage() : (Code::MSG_MAP[$data['code']] ?? '操作失败');
            $data['requestId'] = request()->requestId;
        }

        $logger = App(\Illuminate\Log\Logger::class);

        // 记录日志
        $logger->warning(json_encode($data));

        return $data;
    }

    public static function returnException(Throwable $e, $statusCode = 500): JsonResponse
    {
        $response = new JsonResponse();
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Headers', 'Origin, Content-Type, Cookie, X-CSRF-TOKEN, Accept, Authorization, X-XSRF-TOKEN');
        $response->header('Access-Control-Expose-Headers', 'Authorization, authenticated');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, OPTIONS');
        $response->header('Access-Control-Allow-Credentials', 'true');

        $response->setData(self::processException($e));
        $response->setStatusCode($statusCode);
        $response->send();
        die;// 不中断程序会返回2个json
        return $response;
    }
}
