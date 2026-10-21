<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class WechatCharge extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 充值订单表
        if (!Schema::hasTable('wechat_charge_orders')) {
            Schema::create('wechat_charge_orders', function (Blueprint $table) {
                $table->id()->unsigned();
                $table->integer('uid')->comment("充值人")->unsigned();
                $table->char('nickname', 30)->comment("充值人昵称");
                $table->integer('audit_uid')->comment("审核人")->default(0)->unsigned();
                $table->char('out_trade_no', 32)->unique()->comment('订单号')->unique();
                $table->tinyInteger('status')->default(0)->comment('订单状态 0待审核 1通过 2拒绝')->unsigned();
                $table->tinyInteger('trust_level')->default(0)->comment('可信等级，可信等级决定了充值是否可以无需审核直接到账')->unsigned();
                $table->decimal('total_amount')->default(0)->comment('订单总金额')->unsigned();
                $table->timestamp('audited_at')->comment('审核时间')->nullable()->default(null);
                $table->timestamps();
                $table->engine = 'InnoDB';
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
