<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChargeOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 充值订单表
        if (!Schema::hasTable('charge_orders')) {
            Schema::create('charge_orders', function (Blueprint $table) {
                $table->id()->unsigned();
                $table->integer('uid')->unsigned();
                $table->char('out_trade_no', 32)->comment('订单号')->unique();
                $table->char('trade_no', 32)->comment('支付宝流水号')->default("")->nullable();
                $table->integer('status')->default(0)->comment('订单状态 0待支付 1已支付')->unsigned();
                $table->decimal('total_amount')->default(0)->comment('订单总金额')->unsigned();
                $table->string('qr_code')->comment('二维码内容')->default('');
                $table->timestamp('expired_at')->comment('过期时间')->nullable()->default(null);
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
