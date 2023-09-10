<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShopsTable extends Migration
{

    public function up()
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('city_id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('address');
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->decimal('latitude', 17, 15);
            $table->decimal('longitude', 17, 15);
            $table->string('image_path')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('city_id')->references('id')->on('cities');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shops');
    }
}
