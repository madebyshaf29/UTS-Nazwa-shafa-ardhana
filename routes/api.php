<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MarketplaceCartController;
use App\Http\Controllers\Api\MarketplaceOrderController;
use App\Http\Controllers\Api\MarketplacePaymentController;
use App\Http\Controllers\Api\MarketplaceProductController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('marketplace')->group(function () {
    Route::get('/products', [MarketplaceProductController::class, 'index']);
    Route::get('/products/{id}', [MarketplaceProductController::class, 'show']);
    Route::post('/payments/midtrans/webhook', [MarketplacePaymentController::class, 'midtransWebhook']);
});

Route::middleware('auth:sanctum')->prefix('marketplace')->group(function () {
    Route::get('/cart', [MarketplaceCartController::class, 'show']);
    Route::post('/cart/items', [MarketplaceCartController::class, 'addItem']);
    Route::patch('/cart/items/{itemId}', [MarketplaceCartController::class, 'updateItem']);
    Route::delete('/cart/items/{itemId}', [MarketplaceCartController::class, 'removeItem']);

    Route::post('/orders/checkout', [MarketplaceOrderController::class, 'checkout']);
    Route::get('/orders', [MarketplaceOrderController::class, 'index']);
    Route::get('/orders/{id}', [MarketplaceOrderController::class, 'show']);
    Route::post('/orders/{id}/paid', [MarketplaceOrderController::class, 'markAsPaid']);
});
