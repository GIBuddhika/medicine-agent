<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFileItemTable extends Migration
{

    public function up()
    {
        Schema::create('file_item', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('file_id');
            $table->foreign('item_id')->references('id')->on('items');
            $table->foreign('file_id')->references('id')->on('files');
        });
    }

    public function down()
    {
        Schema::dropIfExists('file_item');
    }
}
