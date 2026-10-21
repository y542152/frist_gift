<?php

namespace App\Type\Models;

use App\Type\BaseType;

class PointChangeType extends BaseType
{
    public float $amount;
    public string $channel;
    public string $orderNo = '';
    public string $desc = '';
}
