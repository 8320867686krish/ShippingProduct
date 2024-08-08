<?php

use App\Http\Controllers\CarrierServiceCallbackController;
use App\Http\Controllers\ProductApiController;
use App\Http\Controllers\RecurringChargeController;
use App\Http\Controllers\SettingsApiController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::post('recurring/create', [RecurringChargeController::class, 'createRecurringCharge']);

Route::middleware(['check.token'])->group(function () {
    Route::get('country', [ProductApiController::class, 'getCountryList']);
    Route::post('products', [ProductApiController::class, 'products']);
    Route::post('settings/save', [SettingsApiController::class, 'store']);
    Route::get('settingBasedToken', [SettingsApiController::class, 'settingBasedToken']);
    Route::resource('setting', SettingsApiController::class);
});
