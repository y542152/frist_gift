<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PointFlowLogsModel extends BaseModel
{
    use HasFactory;

    protected $table = 'point_flow_logs';

    const CHANNEL_CHARGE_REWARD = 'cr'; // 下级充值奖励 in
    const CHANNEL_POINT2COIN = 'ptc'; // 积分转换为金币 out

    const TYPE_IN = 'in';
    const TYPE_OUT = 'out';

    const TYPE_MAP = [
        self::TYPE_IN =>    '收入',
        self::TYPE_OUT =>    '支出',
    ];

    const CHANNEL_MAP = [
        self::CHANNEL_CHARGE_REWARD =>    '下级充值奖励',
        self::CHANNEL_POINT2COIN =>    '积分转换为金币',
    ];
}
