<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LoginLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 登录日志
        if (!Schema::hasTable('login_logs')) {
            Schema::create('login_logs', function (Blueprint $table) {
                $table->id()->unsigned();
                $table->integer('uid')->comment("充值人")->unsigned();
                $table->string('account', 20)->comment('手机号码 用于登陆');
                $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
                $table->integer('ip')->comment('登录ip');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
