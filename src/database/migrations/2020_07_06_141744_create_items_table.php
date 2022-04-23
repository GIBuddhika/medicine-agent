<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_a_shop_listing'); //shop listing or personal listing
            $table->unsignedBigInteger('shop_id');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->integer('category_id'); //rent or sell
            $table->integer('quantity');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('shop_id')->references('id')->on('shops');
        });
    }

    public function down()
    {
        Schema::dropIfExists('items');
    }
}
