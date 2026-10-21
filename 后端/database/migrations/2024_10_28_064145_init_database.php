<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InitDatabase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id('uid')->unsigned();
                $table->string('account', 20)->comment('手机号码 用于登陆')->unique();
                $table->string('qq', 50)->comment('qq')->unique();
                $table->string('qqemail', 60)->comment('qq邮箱')->unique();
                $table->string('nickname', 100)->comment('昵称');
                $table->timestamp('last_login_at')->nullable()->comment('最后登录时间');
                $table->string('avatar_url')->default("")->comment('头像');
                $table->integer('invite_uid')->comment('邀请人')->unsigned()->default(0);
                $table->integer('login_count')->comment('登录次数')->unsigned()->default(0);
                $table->string('pw_encrypt', 32)->comment('密码');
                $table->string('salt', 8);
                $table->decimal('reward_rate', 6)->default(0)->comment('推广抽佣比例，百分比')->unsigned();
                $table->decimal('profit_rate')->default(0.5)->comment('利润，单位元')->unsigned();
                $table->tinyInteger('is_xinfeng')->default(0)->comment('是否可购买信封 0否 1是')->unsigned();
                $table->tinyInteger('trust_level')->default(0)->comment('信任等级')->unsigned();
                $table->tinyInteger('is_delete')->default(0)->comment('是否删除 0否，1是')->unsigned();
                $table->tinyInteger('is_disabled')->default(0)->comment('是否禁用 0否，1是')->unsigned();
                $table->enum('role', ['ADMIN', 'USER'])->default('USER')->comment('角色');
                $table->timestamps();
                $table->engine = 'InnoDB';
            });
            \App\Models\UserModel::query()->insert([
                'account'   =>  'admin',
                'qq'        =>  '123456',
                'qqemail'   =>  '123456@qq.com',
                'nickname'  =>  '管理员',
                'pw_encrypt'  =>  '51960908f3885c238de14a18f3afe76c',
                'salt'  =>  'Ajcndj2.',
                'role'  =>  'ADMIN',
                'last_login_at' =>  date('Y-m-d H:i:s')
            ]);
        }

        if (!Schema::hasTable('user_token')) {
            Schema::create('user_token', function (Blueprint $table) {
                $table->id()->unsigned();
                $table->integer('uid')->unsigned()->unique();
                $table->string('token', 32)->comment('用户账号 用于登陆');
                $table->timestamp('expired_at')->comment('过期时间')->nullable();
                $table->timestamps();
                $table->engine = 'InnoDB';
            });
        }

        if (!Schema::hasTable('user_coin')) {
            Schema::create('user_coin', function (Blueprint $table) {
                $table->integer('uid')->unsigned()->index();
                $table->decimal('coin')->unsigned()->comment('金币')->default(0);
                $table->decimal('point')->unsigned()->comment('积分')->default(0);
                $table->timestamps();
                $table->engine = 'InnoDB';
            });
        }

        if (!Schema::hasTable('coin_flow_logs')) {
            Schema::create('coin_flow_logs', function (Blueprint $table) {
                $table->id()->unsigned();
                $table->integer('uid')->unsigned()->index();
                $table->string('type', 10)->comment('类型，out支出或者in收入');
                $table->char('out_trade_no', 32)->comment('操作单号');
                $table->string('channel', 15)->comment('场景值，如充值、消费等');
                $table->decimal('amount', 11, 2)->unsigned()->comment('金额或者积分或者点数，由场景决定');
                $table->decimal('old_amount', 11, 2)->unsigned()->comment('旧余额');
                $table->decimal('new_amount', 11, 2)->unsigned()->comment('新余额');
                $table->string('desc')->comment('描述');
                $table->timestamps();
                $table->engine = 'InnoDB';
            });
            DB::statement("ALTER TABLE `coin_flow_logs` comment '金币流水记录'");
        }

        // 充值给上级积分，积分不能消费，只能转换为金币
        if (!Schema::hasTable('point_flow_logs')) {
            Schema::create('point_flow_logs', function (Blueprint $table) {
                $table->id()->unsigned();
                $table->integer('uid')->unsigned()->index();
                $table->integer('form_uid')->comment('下级uid')->unsigned()->default(0);
                $table->char('out_trade_no', 32)->comment('操作单号');
                $table->string('type', 10)->comment('类型，out支出或者in收入');
                $table->string('channel', 15)->comment('场景值，如充值、消费等');
                $table->decimal('amount', 11, 2)->unsigned()->comment('金额或者积分或者点数，由场景决定');
                $table->decimal('old_amount', 11, 2)->unsigned()->comment('旧余额');
                $table->decimal('new_amount', 11, 2)->unsigned()->comment('新余额');
                $table->string('desc')->comment('描述');

                $table->index(['form_uid']);
                $table->timestamps();
                $table->engine = 'InnoDB';
            });
            DB::statement("ALTER TABLE `point_flow_logs` comment '积分流水记录'");
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
