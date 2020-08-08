<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSellableItemsTable extends Migration
{
    public function up()
    {
        Schema::create('sellable_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('retail_price', 7, 2)->nullable();
            $table->decimal('wholesale_price', 7, 2)->nullable();
            $table->decimal('wholesale_minimum_quantity', 5, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sellable_items');
    }
}
