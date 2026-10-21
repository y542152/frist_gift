<?php


namespace App\Type;


/**
 * 邮箱重置密码类型
 * Class EmailRegisterType
 */

class EmailResetType extends BaseType
{
   public string $email = '';
   public int $time = 0;
   public string $randomStr = '';
}
