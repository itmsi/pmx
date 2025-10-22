<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ExternalLogin;
use App\Livewire\ExternalDashboard;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Api\GitWebhookController;

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

// Git Webhook Routes
Route::prefix('api/git')->group(function () {
    // Webhook endpoint untuk menerima data dari Git providers
    Route::post('/webhook', [GitWebhookController::class, 'handleWebhook'])->name('git.webhook');
    
    // API untuk mendapatkan git history ticket
    Route::get('/ticket/{ticketId}/history', [GitWebhookController::class, 'getTicketGitHistory'])->name('git.ticket.history');
});
