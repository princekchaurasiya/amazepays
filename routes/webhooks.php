<?php

use App\Http\Controllers\Payment\PaymentCallbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
| Inbound payment callbacks with signature verification.
| These routes have NO session/auth middleware -- they rely on
| gateway-specific signature checks instead.
|
| NOTE: The Unlimit card-payment webhook has been consolidated into
| api.php at POST /api/v1/webhooks/unlimit (with verify.unlimit.signature).
| Do NOT re-add an Unlimit route here — having two callback URLs for the
| same gateway causes split processing and missed reconciliation events.
|
| /upi/webhook delegates to UPIPaymentController because UPI flows
| initiated via Unlimit share the same X-Signature header scheme.
*/

Route::middleware(['throttle:payment-callbacks', 'verify.unlimit.signature'])->group(function () {
    Route::post('/upi/webhook', [PaymentCallbackController::class, 'upi'])->name('upi.webhook');
});
