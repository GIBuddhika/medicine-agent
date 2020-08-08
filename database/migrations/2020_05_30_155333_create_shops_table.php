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
            $table->boolean('is_a_personal_listing')->default(false); //if true, this is not a shop. This just saving data for personal listing
            $table->unsignedBigInteger('file_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('address');
            $table->string('phone');
            $table->string('website')->nullable();
            $table->decimal('latitude', 17, 15);
            $table->decimal('longitude', 17, 15);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('city_id')->references('id')->on('cities');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('file_id')->references('id')->on('files');
        });
    }

    public function down()
    {
        Schema::dropIfExists('shops');
    }
}
