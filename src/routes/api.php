<?php

use App\Constants\UserRoleConstants;
use \App\Http\Controllers\AuthController;
use App\Http\Controllers\DistrictsController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ShopAdminsController;
use App\Http\Controllers\ShopsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::post('/create-account', AuthController::class . '@createAccount');
Route::post('/login', AuthController::class . '@login');
Route::get('/validate', AuthController::class . '@validateToken');
Route::post('/password-reset-request', AuthController::class . '@passwordResetRequest');
Route::post('/reset-password', AuthController::class . '@resetPassword');

Route::get('/districts', DistrictsController::class . '@all');
Route::get('/districts/{id}/cities', DistrictsController::class . '@getCities');

Route::prefix('users')->group(function () {
    Route::middleware(['logged_in_user'])->group(function () {
        Route::get('/{id}', UsersController::class . '@get');
    });
    Route::middleware('any_role:' . UserRoleConstants::SHOP_ADMIN . ',' . UserRoleConstants::ADMIN . '')->group(function () {
        Route::get('/{id}/shops', UsersController::class . '@getShops');
        Route::get('/{id}/items', UsersController::class . '@getItems');
    });
    Route::middleware(['admin'])->group(function () {
        Route::get('/{id}/shop-admins', UsersController::class . '@getShopAdmins');
        Route::get('/{id}/personal-items', UsersController::class . '@getPersonalItems');
    });
});

Route::prefix('shops')->group(function () {
    // Route::get('/', ShopsController::class . '@all');
    Route::get('/{id}', ShopsController::class . '@one');

    Route::middleware(['admin'])->group(function () {
        Route::post('/', ShopsController::class . '@create');
        Route::patch('/{id}', ShopsController::class . '@update');
        Route::delete('/{id}', ShopsController::class . '@delete');
    });
    Route::middleware('any_role:' . UserRoleConstants::SHOP_ADMIN . ',' . UserRoleConstants::ADMIN . '')->group(function () {
        Route::get('/{id}/items', ShopsController::class . '@geItems');
    });
});

Route::prefix('items')->group(function () {
    Route::get('/', ItemsController::class . '@all');
    Route::get('/{slug}', ItemsController::class . '@get');
    Route::middleware('any_role:' . UserRoleConstants::SHOP_ADMIN . ',' . UserRoleConstants::ADMIN . '')->group(function () {
        Route::post('/', ItemsController::class . '@create');
        Route::patch('/{id}', ItemsController::class . '@update');
        Route::delete('/{id}', ItemsController::class . '@delete');
    });
});

Route::prefix('orders')->group(function () {
    Route::middleware(['customer'])->group(function () {
        Route::post('/', OrdersController::class . '@create');
        Route::get('/un-collected', OrdersController::class . '@getUnCollectedOrderItems');
        Route::get('/collected', OrdersController::class . '@getCollectedOrderItems');
    });
});

Route::prefix('admin')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::middleware('any_role:' . UserRoleConstants::SHOP_ADMIN . ',' . UserRoleConstants::ADMIN . '')->group(function () {
            Route::get('/shops', OrdersController::class . '@getShopOrderItemsForAdmin');
            Route::get('/personal', OrdersController::class . '@getPersonalOrderItemsForAdmin');
            Route::patch('/item-order/{itemOrderId}', OrdersController::class . '@markItemOrderAsCollected');
        });
    });
});

Route::prefix('shop-admins')->group(function () {
    Route::middleware(['admin',])->group(function () {
        Route::post('/', ShopAdminsController::class . '@create');
        Route::get('/', ShopAdminsController::class . '@all');
        Route::patch('/{id}', ShopAdminsController::class . '@update');
        Route::delete('/{id}', ShopAdminsController::class . '@delete');
    });
});
