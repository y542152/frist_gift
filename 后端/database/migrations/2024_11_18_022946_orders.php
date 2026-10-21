<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Orders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 订单表
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id()->unsigned();
                $table->integer('operate_uid')->unsigned();
                $table->char('main_order_sn', 32)->comment('主订单号');
                $table->char('order_sn', 20)->comment('订单号')->unique();
                $table->integer('status')->default(1)->comment('订单状态 1默认 2待揽件 3已揽件 4取号失败')->unsigned();
                $table->tinyInteger('is_xinfeng')->default(0)->comment('是否信封 1是 信封，0否 礼品')->unsigned();

                $table->integer('kuaidi_id')->default(0)->comment('快递id')->unsigned();
                $table->char('kuaidi_name', 40)->default('')->comment('快递名称');
                $table->char('platform_name', 12)->default('')->comment('平台名称');

                $table->integer('goods_id')->default(0)->comment('商品id')->unsigned();
                $table->char('goods_name', 30)->default('')->comment('礼品名称');

                $table->decimal('price')->default(0)->unsigned()->comment('商品价格');
                $table->decimal('express_price')->default(0)->unsigned()->comment('快递价格');
                $table->decimal('profit_rate')->default(0)->unsigned()->comment('利润，单位元');
                $table->decimal('total_amount')->default(0)->unsigned()->comment('单个售价');

                $table->char('send_name', 10)->comment('发货人名称');
                $table->char('send_mobile', 11)->comment('发货人电话');

                $table->char('send_province', 10)->comment('发货人省');
                $table->char('send_city', 11)->comment('发货人市');
                $table->char('send_district', 20)->comment('发货人区');
                $table->string('send_address', 50)->comment('发货人地址');

                $table->char('storage_province', 10)->comment('仓库发货省');
                $table->char('storage_city', 11)->comment('仓库发货市');
                $table->char('storage_district', 20)->comment('仓库发货区');
                $table->string('storage_address', 50)->comment('仓库发货地址');

                $table->char('receive_name', 10)->comment('收货人名称');
                $table->char('receive_mobile', 11)->comment('收货人电话');
                $table->char('receive_province', 10)->comment('省');
                $table->char('receive_city', 11)->comment('市');
                $table->char('receive_district', 20)->comment('区');
                $table->string('receive_address', 50)->comment('收货地址');
                $table->char('zip_code', 6)->comment('邮编')->default(null)->nullable();
                $table->char('kuaidi_sn', 30)->comment('快递单号')->default('');
                $table->char('other_kuaidi_sn', 30)->comment('无货源订单的上家快递单号')->default('');
                $table->string('error')->comment('错误信息')->default('');
                $table->timestamps();
                $table->engine = 'InnoDB';
            });
        }
    }
}
