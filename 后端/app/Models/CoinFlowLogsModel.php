<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoinFlowLogsModel extends BaseModel
{
    use HasFactory;

    protected $table = 'coin_flow_logs';

    const CHANNEL_CHARGE = 'charge'; // 充值 in
    const CHANNEL_POINT2COIN = 'ptc'; // 积分转换为金币 in
    const CHANNEL_BUY = 'buy'; // 购买 out
    const CHANNEL_MANUAL = 'manual'; // 手动更改 in|out

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';

    const TYPE_MAP = [
        self::TYPE_IN =>    '收入',
        self::TYPE_OUT =>    '支出',
    ];

    const CHANNEL_MAP = [
        self::CHANNEL_CHARGE        =>    '充值',
        self::CHANNEL_POINT2COIN    =>    '积分转换为金币',
        self::CHANNEL_BUY           =>    '购买',
        self::CHANNEL_MANUAL        =>    '手动更改',
    ];
}
