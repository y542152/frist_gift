<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserModel extends BaseModel
{
    use HasFactory;

    protected $primaryKey = 'uid';

    protected $table = 'users';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'account','nickname', 'pw_encrypt', 'salt', 'avatar_url', 'last_login_at', 'invite_uid', 'qq', 'qqemail'
    ];

    public const TOKEN_EXPIRED_TIME =  3600 * 24; // 24小时
}
