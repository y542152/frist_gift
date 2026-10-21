<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserCoinModel extends BaseModel
{
    use HasFactory;

    protected $table = 'user_coin';

    protected $primaryKey = 'uid';

    protected $fillable = [
        'uid', 'coin' , 'point'
    ];

    /**
     * 强制转换的属性
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:00',
        'updated_at' => 'datetime:Y-m-d H:i:00',
    ];
}
