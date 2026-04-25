<?php

use App\Http\Controllers\Payment\MockRazorpayController;
use App\Http\Controllers\Payment\PaymentSessionController;
use App\Http\Controllers\WoohooProcessingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Payment Gateway Routes
|--------------------------------------------------------------------------
| Initiation, return URLs, and webhook endpoints for all payment gateways.
*/

Route::middleware('throttle:payments')->group(function () {
    Route::match(['get', 'post'], '/unlimit/return', [WoohooProcessingController::class, 'handleReturn'])
        ->name('unlimit.return');
});

Route::get('/payment/success', function () {
    // Cast and validate 'amount' — it comes from the query string and must never
    // be passed raw to the view (reflected XSS / spoofing vector).
    $raw = request()->query('amount');
    $amount = is_numeric($raw) ? number_format((float) $raw, 2) : null;

    return Inertia::render('Checkout/Status', [
        'status' => 'success',
        'msg' => session('success') ?: 'Payment successful',
        'amount' => $amount,
    ]);
})->name('payment.success');

Route::get('/payment/failed', function () {
    $raw = request()->query('amount');
    $amount = is_numeric($raw) ? number_format((float) $raw, 2) : null;

    return Inertia::render('Checkout/Status', [
        'status' => 'failure',
        'msg' => session('error') ?: 'Payment failed',
        'amount' => $amount,
    ]);
})->name('payment.failed');

Route::get('/payment/processed', function () {
    $raw = request()->query('amount');
    $amount = is_numeric($raw) ? number_format((float) $raw, 2) : null;

    return Inertia::render('Checkout/Status', [
        'status' => 'success',
        'msg' => 'Payment processed',
        'amount' => $amount,
    ]);
})->name('payment.processed');

Route::get('/order-failure', function () {
    return Inertia::render('Checkout/Status', [
        'status' => 'failure',
        'msg' => 'Order could not be completed.',
        'amount' => null,
    ]);
})->name('order.failure');

Route::middleware(['auth', 'throttle:payments'])->group(function () {
    Route::post('/payment/upi', [PaymentSessionController::class, 'upi'])
        ->middleware('idempotency:web.payment.upi')
        ->name('payment.upi');
    // Canonical: netbank (netbnk was a legacy typo)
    Route::post('/payment/netbank', [PaymentSessionController::class, 'netbanking'])
        ->middleware('idempotency:web.payment.netbank')
        ->name('payment.netbank');

    // Legacy typo alias: preserve POST method.
    Route::post('/payment/netbnk', function () {
        return redirect()->route('payment.netbank', [], 308);
    })->name('payment.netbnk.legacy');
    Route::post('/payment/unlimit', [PaymentSessionController::class, 'unlimit'])
        ->middleware('idempotency:web.payment.unlimit')
        ->name('payment.unlimit');
    Route::post('/payment/razorpay', [PaymentSessionController::class, 'razorpay'])
        ->middleware('idempotency:web.payment.razorpay')
        ->name('payment.razorpay');
    Route::post('/payment/razorpay/verify', [PaymentSessionController::class, 'razorpayVerify'])
        ->middleware('idempotency:web.payment.razorpay.verify')
        ->name('payment.razorpay.verify');

    // Mock Razorpay (local/testing only)
    if (app()->environment(['local', 'testing'])) {
        Route::post('/payment/mock-razorpay', [MockRazorpayController::class, 'initiate'])
            ->middleware('idempotency:web.payment.mock_razorpay')
            ->name('payment.mock_razorpay');
    }
});

// Mock gateway (local/testing only)
if (app()->environment(['local', 'testing'])) {
    Route::middleware(['throttle:payments'])->group(function () {
        Route::get('/mock/razorpay/pay/{merchantOrderId}', [MockRazorpayController::class, 'pay'])
            ->name('mock.razorpay.pay');
        Route::post('/mock/razorpay/callback/{merchantOrderId}', [MockRazorpayController::class, 'callback'])
            ->name('mock.razorpay.callback');
    });
}

Route::middleware('throttle:payments')->group(function () {
    Route::match(['GET', 'POST'], '/upi/return', fn () => redirect()->to(route('unlimit.return', request()->query())))
        ->name('upi.return');
});

// -----------------------------------------------------------------------------
// Legacy VD/KGen payment endpoints (Phase 4)
// -----------------------------------------------------------------------------
// These endpoints were part of the pre-Phase-3 voucher flows and are now retired.
// For one release, keep them as redirects so old bookmarks / integrations don't
// hard-fail immediately. They will be removed in Phase 6/PR 7.
//
// Redirects must preserve POST semantics for one release → use 308.
Route::middleware('throttle:payments')->group(function () {
    // Old ValueDesign payment initiation (retired)
    Route::match(['GET', 'POST'], '/vd-payment', fn () => redirect('/', 308))->name('legacy.vd-payment.redirect');

    // Old KGen payment flow (retired)
    Route::match(['GET', 'POST'], '/kgen-payment/initiate', fn () => redirect('/', 308))->name('legacy.kgen-payment.initiate.redirect');
    Route::match(['GET', 'POST'], '/kgen-payment/success/{any?}', fn () => redirect('/', 308))
        ->where('any', '.*')
        ->name('legacy.kgen-payment.success.redirect');
    Route::match(['GET', 'POST'], '/kgen-payment/failed/{any?}', fn () => redirect('/', 308))
        ->where('any', '.*')
        ->name('legacy.kgen-payment.failed.redirect');
    Route::match(['GET', 'POST'], '/kgen-payment/{any}', fn () => redirect('/', 308))
        ->where('any', '.*')
        ->name('legacy.kgen-payment.catchall.redirect');
});
