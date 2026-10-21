<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InitAddress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('address')) {
            Schema::create('address', function (Blueprint $table) {
                $table->id()->unsigned();
                $table->integer('uid')->unsigned();
                $table->char('name', 10)->comment('收货人名称');
                $table->char('phone', 11)->comment('收货人电话');
                $table->char('province', 10)->comment('省');
                $table->char('city', 11)->comment('市');
                $table->char('district', 10)->comment('区');
                $table->tinyInteger('status')->comment('地址状态, 0正常 1禁用')->default(0);
                $table->string('address', 50)->comment('收货地址');
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
