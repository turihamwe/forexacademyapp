<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('register');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/payments/initiate', [PaymentController::class, 'initiate'])->name('payments.initiate');
});

Route::get('/pricing', [PaymentController::class, 'pricing'])->name('pricing');

Route::middleware(['auth', 'superadmin'])->prefix('admin/settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::put('/', [SettingsController::class, 'update'])->name('admin.settings.update');
});

Route::post('/webhooks/yo-payments', [WebhookController::class, 'yoPayments'])
    ->name('webhooks.yo-payments');
