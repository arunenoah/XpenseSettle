<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DeviceTokenController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Authentication Routes (Mobile App)
 * 
 * These endpoints are for mobile app authentication and do not require CSRF
 */
Route::post('/auth/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/auth/logout', [AuthController::class, 'logout']);

/**
 * Device Token Routes (For Push Notifications)
 *
 * These endpoints are called by the mobile app to register/manage
 * FCM device tokens for push notifications
 */
Route::middleware('auth:sanctum')->group(function () {
    // Register a new device token
    Route::post('/device-tokens', [DeviceTokenController::class, 'register'])
        ->name('api.device-tokens.register');

    // List all device tokens for current user
    Route::get('/device-tokens', [DeviceTokenController::class, 'list'])
        ->name('api.device-tokens.list');

    // Remove/deactivate a device token
    Route::delete('/device-tokens', [DeviceTokenController::class, 'remove'])
        ->name('api.device-tokens.remove');
});
