<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordRecoveryController;
use App\Http\Controllers\Auth\RegisterFlowController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Support\Facades\Route;

/*
| Autenticazione, registrazione e verifica email.
*/

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/registrati/completa-anagrafica', [RegisterFlowController::class, 'completeAnagrafica'])->name('register.complete');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])->name('verification.resend');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/registrati', [RegisterFlowController::class, 'show'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'authenticate'])->name('login.post');

    Route::get('/recupera-password', [PasswordRecoveryController::class, 'create'])->name('password.request');
    Route::post('/recupera-password', [PasswordRecoveryController::class, 'store'])->name('password.email');
    Route::get('/recupera-password/{token}', [PasswordRecoveryController::class, 'edit'])->name('password.reset');
    Route::post('/password/reimposta', [PasswordRecoveryController::class, 'update'])->name('password.update');
});

/*
| Step registrazione (sessione registering_user_id, non Auth::login).
| Fuori da guest: accessibili anche durante register.complete (utente già loggato).
*/
Route::middleware('throttle:30,1')->group(function (): void {
    Route::post('/anagrafica/check', [RegisteredUserController::class, 'checkFiscale'])->name('anagrafica.check');
    Route::post('/anagrafica/update', [RegisteredUserController::class, 'updateAnagrafica'])->name('anagrafica.update');
});

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware(['auth', 'signed'])
    ->name('verification.verify');

Route::any('/logout', [LoginController::class, 'logout'])->name('logout');
