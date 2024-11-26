<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductApiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\RecurringChargeController;
use App\Models\User;

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

Route::get('/', [HomeController::class, 'index'])->middleware(['verify.shopify', 'verify.shop'])->name('home');
Route::get('/{path?}', [HomeController::class, 'common'])->where('path', '.*');

Route::get('demo', function () {
    $allUser =  User::get();
    foreach ($allUser as $value) {
        if (@$value['password']) {
            $token = $value->password; // Assuming the token is stored in the password field
            $shopDomain = $value->name;
            $shopUrl = "https://{$shopDomain}/admin/api/2023-10/webhooks.json";
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
            ])->get($shopUrl);
            $shopJsonResponse = $response->json();
            $array = ['shop' => $value->name, 're' => $shopJsonResponse];
            dump($array);
        }
    }
});
