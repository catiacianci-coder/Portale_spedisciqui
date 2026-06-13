<?php

use App\Http\Controllers\LiccardiTmsWebhookController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

/*
| Webhook esterni (no CSRF — vedi bootstrap/app.php).
*/

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');
Route::match(['get', 'post'], '/webhook/liccardi-tms', LiccardiTmsWebhookController::class)
    ->name('webhook.liccardi-tms');
