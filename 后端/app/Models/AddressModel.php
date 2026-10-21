<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AddressModel extends Model
{
    use HasFactory;

    protected $table = 'address';

    public const STATUS_ON = 1; // 启用
    public const STATUS_OFF = 0;
}
