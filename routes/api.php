<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);

Route::get('/pricing', [PaymentController::class, 'pricing']);

Route::post('/webhooks/yo-payments', [WebhookController::class, 'yoPayments']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/dashboard/modules', [DashboardController::class, 'index']);
    Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
});

Route::middleware(['auth:sanctum', 'superadmin'])->prefix('admin/settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index']);
    Route::put('/', [SettingsController::class, 'update']);
});
