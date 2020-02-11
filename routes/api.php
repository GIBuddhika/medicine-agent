<?php

use \App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/create-account', AuthController::class . '@createAccount');
Route::post('/sign-in', AuthController::class . '@signIn');
