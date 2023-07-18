<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('item_order_id');
            $table->string('payment_type'); //shop/online
            $table->decimal('payment_amount', 7, 2);
            $table->integer('duration')->nullable();
            $table->string('online_payment_id')->nullable();
            $table->text('log')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('order_id')->references('id')->on('orders');
            $table->foreign('item_order_id')->references('id')->on('item_order');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
