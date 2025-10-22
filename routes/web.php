<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ExternalLogin;
use App\Livewire\ExternalDashboard;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\WebhookController;

Route::get('/', function () {
    return redirect('/admin/login');
});

// Google Authentication Routes
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');

// External Dashboard Routes
Route::prefix('external')->name('external.')->group(function () {
    Route::get('/{token}', ExternalLogin::class)->name('login');
    Route::get('/{token}/dashboard', ExternalDashboard::class)->name('dashboard');
});

// Git Webhook Route - tanpa middleware apapun
Route::post('/webhook/git', [WebhookController::class, 'gitWebhook'])->name('webhook.git');

// Test route sederhana
Route::post('/test-webhook', function() {
    return response()->json(['message' => 'Webhook test successful', 'timestamp' => now()]);
});
