<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('name');
            $table->string('location');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down()
    {
        Schema::dropIfExists('files');
    }
}
