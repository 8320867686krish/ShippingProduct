<?php

use App\Http\Controllers\CarrierServiceCallbackController;
use App\Http\Controllers\ProductApiController;
use App\Http\Controllers\RecurringChargeController;
use App\Http\Controllers\SettingsApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('carrier/callback', [CarrierServiceCallbackController::class, 'handleCallback']);
Route::post('/webhooks/app_subscriptions', [WebhookController::class, 'handleAppSubscriptions']);
Route::get('demo', [SettingsApiController::class, 'demo']);

Route::post('recurring/create', [RecurringChargeController::class, 'createRecurringCharge']);
Route::get('recurring/create', [RecurringChargeController::class, 'createRecurringCharge']);
Route::get('recurring/confirm', [RecurringChargeController::class, 'confirmRecurringCharge']);

Route::get('genrateurl/{shop}',[SettingsApiController::class,'genrateurl']);
Route::post('saveauth',[SettingsApiController::class,'saveauth']);

Route::middleware(['check.token'])->group(function () {
    Route::get('country', [ProductApiController::class, 'getCountryList']);
    Route::post('products', [ProductApiController::class, 'products']);
    Route::post('settings/save', [SettingsApiController::class, 'store']);
    Route::get('settingBasedToken', [SettingsApiController::class, 'settingBasedToken']);
    Route::resource('setting', SettingsApiController::class);
    Route::get('plans', [SettingsApiController::class, 'getUserBasedPlans']);
});
