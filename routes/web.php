<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
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
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/modules/{id}', [ModuleController::class, 'show'])->name('modules.show');
    Route::post('/modules/{id}/complete', [ModuleController::class, 'complete'])->name('modules.complete');
    Route::post('/payments/initiate', [PaymentController::class, 'initiate'])->name('payments.initiate');
});

Route::get('/pricing', [PaymentController::class, 'pricing'])->name('pricing');

Route::middleware(['auth', 'superadmin'])->prefix('admin/settings')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('admin.settings.index');
    Route::put('/', [SettingsController::class, 'update'])->name('admin.settings.update');
});

Route::post('/webhooks/yo-payments', [WebhookController::class, 'yoPayments'])
    ->name('webhooks.yo-payments');

Route::post('/webhooks/yo-payments/failure', [WebhookController::class, 'yoPaymentsFailure'])
    ->name('webhooks.yo-payments.failure');
