<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WechatChargeOrdersModel extends BaseModel
{
    use HasFactory;

    protected $table = 'wechat_charge_orders';

    public const STATUS_WAIT = 0;
    public const STATUS_PASSED = 1;
    public const STATUS_REJECTED = 2;

    public const STATUS_MAP = [
        self::STATUS_WAIT => '待审核',
        self::STATUS_PASSED => '已通过',
        self::STATUS_REJECTED => '已拒绝',
    ];

    public const TRUST_LEVE_DISTRUST = 0;
    public const TRUST_LEVE_NORMAL = 1;
    public const TRUST_LEVE_TRUST = 2;

    public const TRUST_LEVE_MAP = [
        self::TRUST_LEVE_DISTRUST => '非信任',
        self::TRUST_LEVE_NORMAL => '一般信任',
        self::TRUST_LEVE_TRUST => '超级信任',
    ];
}
