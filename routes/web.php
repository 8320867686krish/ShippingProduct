<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductApiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\RecurringChargeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('recurring/create', [RecurringChargeController::class, 'createRecurringCharge']);
Route::get('recurring/confirm', [RecurringChargeController::class, 'confirmRecurringCharge']);

Route::post('customers/update', [WebhookController::class, 'customersUpdate']);
Route::post('customers/delete', [webhookController::class, 'customersDelete']);
Route::post('shop/update', [webhookController::class, 'shopUpdate']);
Route::post('products/update', [webhookController::class, 'handleProductUpdateWebhook']);

Route::get('/', [HomeController::class, 'index'])->middleware(['verify.shop', 'verify.shopify'])->name('home');
Route::get('/{path?}', [HomeController::class, 'common'])
    ->where('path', '^(?!uploads).')
    ->where('path', '..(?!jpg|jpeg|png|gif|bmp|ico|webp).')
    ->where('path', '.*')
    ->fallback();

