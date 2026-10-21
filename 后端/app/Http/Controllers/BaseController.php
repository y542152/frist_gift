<?php


namespace App\Http\Controllers;


use App\Exceptions\Error;
use App\Supports\Code;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BaseController extends Controller
{
    // 接口入参格式 有设置的话 请求进来首先要通过这个
    protected array $paramRules = [];
    protected array $requestData = [];
    protected array $paramRulesMessage = [];

    protected $uid;

    protected Request $request;

    // 请求初始化一些东西
    protected function _init()
    {
        $this->request = request();
        $this->requestData = $this->request->all();

        // 检查入参规则
        if($this->paramRules) {
            $validator = Validator::make($this->requestData, $this->paramRules, $this->paramRulesMessage);
            if($validator->fails()) {
                $errors = $validator->errors()->toArray();
                throw new Error(Code::REQUEST_PARAM_ERROR, $errors[array_key_first($errors)][0]);
            }
        }

        if (request()->user) {
            $this->uid = request()->user ? request()->user->uid : null;
        }
    }

    protected function getUid()
    {
        return $this->uid;
    }

    // 根据不同异常处理返回数据
    protected function handleException(\Throwable $e): array
    {
        switch (get_class($e)) {
            case QueryException::class:
                logger()->warning('QueryException :'. $e->getMessage());
                return $this->returnData('', Code::QUERY_FAILED);

            default:
                logger()->warning(sprintf(
                    "%s\r\n%s:%s\r\n code:%s:%s",
                    get_class($e),
                    $e->getFile(),
                    $e->getLine(),
                    $e->getCode(),
                    $e->getMessage()
                ));
                break;
        }

        if (in_array($e->getCode(), [0, 500], true)) {
            return $this->returnData(Code::MSG_MAP[Code::SYSTEM_FAILED], Code::SYSTEM_FAILED);
        } else {
            return $this->returnData($e->getMessage(),  $e->getCode());
        }
    }

    // 组装返回数据格式
    protected function returnData($data = '', $code = Code::SUCCESS): array
    {
        if(Code::SUCCESS === $code) {
            $return = [
                'code'  =>  $code,
                'data'  =>  $data,
                'requestId' => request()->requestId
            ];
        } else {
            $return = [
                'code'  =>  $code,
                'errorMsg'  =>  $data ? $data : (Code::MSG_MAP[$code] ?? '系统繁忙'),
                'requestId' => request()->requestId
            ];
        }
        logger()->info('response', [
            'return'    =>  json_encode($return)
        ]);
        return $return;
    }
}
