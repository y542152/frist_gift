<?php


namespace App\Http\Controllers;

use App\Exceptions\Error;
use App\Exceptions\FrequentActionError;
use App\Services\UserService;
use App\Supports\Code;
use Illuminate\Support\Facades\Cache;

class Notify extends BaseController
{
    public function ali(): array
    {
        try {
            parent::_init();

            if (
                !array_key_exists('out_trade_no', $this->requestData) ||
                !array_key_exists('total_amount', $this->requestData)
            ) {
                throw new Error(Code::REQUEST_PARAM_ERROR);
            }

            $lock = Cache::lock('aliNotifyUrl-handle-' . $this->requestData['out_trade_no'], 10);

            if ($lock->get()) {
                (new UserService())->queryOrder($this->requestData['out_trade_no'], $this->requestData['total_amount']);
                $lock->release();
                return $this->returnData([]);
            } else {
                throw new FrequentActionError(Code::FREQUENT_OPERATION);
            }
        } catch (FrequentActionError $e) {
            return $this->handleException($e);
        }  catch (\Throwable $e) {
            isset($lock) && $lock->release();
            return $this->handleException($e);
        }
    }
}
