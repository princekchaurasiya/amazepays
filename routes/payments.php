<?php

use App\Http\Controllers\Payment\MockRazorpayController;
use App\Http\Controllers\Payment\PaymentSessionController;
use App\Http\Controllers\WoohooProcessingController;
use App\Http\Controllers\CCAvenueController;
use App\Models\Order;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

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
    // If we have an order context, go through the processing page only when the order
    // is not yet in a terminal state. Otherwise render the success page (avoid loops).
    $orderId = (int) request()->query('order_id', session('checkout_order_id', 0));
    if ($orderId > 0 && Auth::check()) {
        $order = Order::query()
            ->whereKey($orderId)
            ->where('user_id', Auth::id())
            ->first();

        if ($order) {
            $st = strtolower((string) ($order->status ?? ''));
            $terminal = in_array($st, ['fulfilled', 'failed', 'cancelled', 'refunded'], true);
            if (! $terminal) {
                return redirect()->route('payment.processing', ['order_id' => $orderId]);
            }
        }
    }

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

Route::get('/payment/cancelled', function () {
    $raw = request()->query('amount');
    $amount = is_numeric($raw) ? number_format((float) $raw, 2) : null;

    return Inertia::render('Checkout/Status', [
        'status' => 'failure',
        'msg' => session('warning') ?: 'Payment cancelled',
        'amount' => $amount,
    ]);
})->name('payment.cancelled');

Route::get('/payment/disconnected', function () {
    $raw = request()->query('amount');
    $amount = is_numeric($raw) ? number_format((float) $raw, 2) : null;

    return Inertia::render('Checkout/Status', [
        'status' => 'failure',
        'msg' => session('warning') ?: 'Payment disconnected',
        'amount' => $amount,
    ]);
})->name('payment.disconnected');

Route::get('/payment/processing', function () {
    $orderId = (int) request()->query('order_id', session('checkout_order_id', 0));
    if ($orderId <= 0 || ! Auth::check()) {
        return redirect()->route('my-order');
    }

    $order = Order::query()
        ->whereKey($orderId)
        ->where('user_id', Auth::id())
        ->first();

    if (! $order) {
        session()->forget('checkout_order_id');
        return redirect()->route('my-order');
    }

    $status = strtolower((string) ($order->status ?? 'processing'));
    $amount = $order->grand_total_minor !== null ? ((int) $order->grand_total_minor) / 100 : 0;

    // If order already reached a terminal status, do not show a spinner page.
    if (in_array($status, ['completed', 'complete', 'fulfilled'], true)) {
        return redirect()->route('payment.success', ['amount' => $amount]);
    }
    if ($status === 'failed') {
        return redirect()->route('payment.failed', ['amount' => $amount]);
    }
    if (in_array($status, ['cancelled', 'canceled'], true)) {
        return redirect()->route('payment.cancelled', ['amount' => $amount]);
    }

    return Inertia::render('Checkout/Processing', [
        'order_id' => $order->id,
        'status' => $status,
        'amount' => $amount,
        'merchant_order_id' => (string) ($order->order_number ?? ''),
        'payment_id' => null,
    ]);
})->middleware(['web', 'auth'])->name('payment.processing');

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
    Route::post('/payment/ccavenue', [CCAvenueController::class, 'processPayment'])
        ->middleware('idempotency:web.payment.ccavenue')
        ->name('payment.ccavenue');
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

// Legacy VD/KGen payment endpoints removed as part of voucher-distributor rename.
