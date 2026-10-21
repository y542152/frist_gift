<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrdersModel extends BaseModel
{
    use HasFactory;

    protected $table = 'orders';

    public const STATUS_WAIT_PREORDER = 1;
    public const STATUS_WAIT_COLLECT = 2;
    public const STATUS_COLLECTED = 3;
    public const STATUS_PREORDER_FAILED = 4;
    public const STATUS_CANCELED = 5;

    public const STATUS_MAP = [
        self::STATUS_WAIT_PREORDER =>  '待取号',
        self::STATUS_WAIT_COLLECT =>  '待揽件',
        self::STATUS_COLLECTED =>  '已揽件',
        self::STATUS_PREORDER_FAILED =>  '取号失败',
        self::STATUS_CANCELED =>  '已取消',
    ];
}
