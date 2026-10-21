<?php


namespace App\Services;


use App\Exceptions\Error;
use App\Models\ChargeOrdersModel;
use App\Repositories\ChargeOrdersRepository;
use App\Repositories\UserRepository;
use App\Sdk\f2fpay\builder\AlipayTradePayContentBuilder;
use App\Sdk\f2fpay\builder\AlipayTradePrecreateContentBuilder;
use App\Sdk\f2fpay\builder\GoodsDetail;
use App\Sdk\f2fpay\service\AlipayTradeService;
use App\Supports\Code;
use App\Supports\Helper;
use Illuminate\Support\Facades\Config;


class NotifyService
{
    public function encryptPw(string $password, string $salt = ''): string
    {
        return md5(implode('@@encrypt@@',[
            $password,
            $salt
        ]));
    }
}
