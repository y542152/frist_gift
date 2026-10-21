<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargeOrdersModel extends BaseModel
{
    use HasFactory;

    protected $table = 'charge_orders';

    public const STATUS_WAIT = 0; // 订单状态 0待支付 1已支付
    public const STATUS_PAYED = 1; // 订单状态 0待支付 1已支付

    public const STATUS_MAP = [
        self::STATUS_WAIT => '待支付',
        self::STATUS_PAYED => '已支付 ',
    ];
}
