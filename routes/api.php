<?php

use \App\Http\Controllers\AuthController;
use App\Http\Controllers\ShopsController;
use Illuminate\Support\Facades\Route;

Route::post('/create-account', AuthController::class . '@createAccount');
Route::post('/sign-in', AuthController::class . '@signIn');


Route::prefix('shops')->group(function () {
    Route::get('/', ShopsController::class . '@all');
    Route::get('/{id}', ShopsController::class . '@one');

    Route::middleware(['logged_in_user',])->group(function () {
        Route::post('/', ShopsController::class . '@create');
        Route::patch('/{id}', ShopsController::class . '@update');
        Route::delete('/{id}', ShopsController::class . '@delete');
    });
});
