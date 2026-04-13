<?php

use App\Http\Controllers\UnlimitPaymentController;
use App\Http\Controllers\UPIPaymentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
| Inbound payment callbacks with signature verification.
| These routes have NO session/auth middleware -- they rely on
| gateway-specific signature checks instead.
|
| /upi/webhook is delegated to the same handler as Unlimit card webhooks
| because UPI flows initiated via Unlimit use the same Signature header.
*/

Route::middleware(['throttle:payment-callbacks', 'verify.unlimit.signature'])->group(function () {
    Route::post('/unlimit/webhook', [UnlimitPaymentController::class, 'webhook'])->name('unlimit.webhook');
    Route::post('/upi/webhook', [UPIPaymentController::class, 'webhook'])->name('upi.webhook');
});
