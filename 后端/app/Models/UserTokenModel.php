<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserTokenModel extends BaseModel
{
    use HasFactory;

    protected $table = 'user_token';
}
