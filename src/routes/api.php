<?php

use App\Constants\UserRoleConstants;
use App\Http\Controllers\AccountSummaryController;
use App\Http\Controllers\ActiveIngredientsController;
use \App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandsController;
use App\Http\Controllers\DistrictsController;
use App\Http\Controllers\ItemsController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\ReviewsController;
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
Route::get('/active-ingredient-names', ActiveIngredientsController::class . '@getNames');
Route::get('/brand-names', BrandsController::class . '@getNames');

Route::prefix('users')->group(function () {
    Route::middleware(['logged_in_user'])->group(function () {
        Route::get('/{id}', UsersController::class . '@get');
        Route::patch('/{id}', UsersController::class . '@update');
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
    Route::get('/similar-products', ItemsController::class . '@getSimilarProducts');
    Route::get('/{slug}', ItemsController::class . '@get');
    Route::get('/{slug}/reviews', ItemsController::class . '@getReviews');
    Route::middleware('any_role:' . UserRoleConstants::SHOP_ADMIN . ',' . UserRoleConstants::ADMIN . '')->group(function () {
        Route::post('/', ItemsController::class . '@create');
        Route::patch('/{id}', ItemsController::class . '@update');
        Route::delete('/{id}', ItemsController::class . '@delete');
    });
});

Route::prefix('orders')->group(function () {
    Route::middleware(['customer'])->group(function () {
        Route::post('/', OrdersController::class . '@create');
        Route::get('/', OrdersController::class . '@getOrders');
        Route::post('/extend', OrdersController::class . '@extend');
        Route::get('/un-collected', OrdersController::class . '@getUnCollectedOrderItems');
        Route::get('/collected', OrdersController::class . '@getCollectedOrderItems');
        Route::get('/cancelled', OrdersController::class . '@getCancelledOrderItems');
        Route::post('/{orderId}/cancel', OrdersController::class . '@cancelOrderItem');
    });
});

Route::prefix('admin')->group(function () {
    Route::prefix('orders')->group(function () {
        Route::middleware('any_role:' . UserRoleConstants::SHOP_ADMIN . ',' . UserRoleConstants::ADMIN . '')->group(function () {
            Route::get('/', OrdersController::class . '@getOrderForAdmin');
            Route::patch('/item-order/{itemOrderId}/collected', OrdersController::class . '@markItemOrderAsCollected');
            Route::patch('/item-order/{itemOrderId}/received', OrdersController::class . '@markItemOrderAsReceived');
            Route::patch('/item-order/{itemOrderId}/cancel', OrdersController::class . '@markItemOrderAsCancelled');
            Route::get('/item-order/{itemOrderId}/payments', OrdersController::class . '@getOrderItemPaymentData');
            Route::patch('/item-order/{itemOrderId}/refund', OrdersController::class . '@refundOrderItem');
        });
    });
    Route::middleware('any_role:' . UserRoleConstants::SHOP_ADMIN . ',' . UserRoleConstants::ADMIN . '')->group(function () {
        Route::get('/account-summary', AccountSummaryController::class . '@filterAccountSummary');
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

Route::prefix('reviews')->group(function () {
    Route::middleware(['customer',])->group(function () {
        Route::post('/', ReviewsController::class . '@create');
        Route::patch('/{id}', ReviewsController::class . '@update');
        Route::delete('/{id}', ReviewsController::class . '@delete');
    });
    Route::get('/', ReviewsController::class . '@all');
});
