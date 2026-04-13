<?php

use App\Http\Controllers\KGenPaymentController;
use App\Http\Controllers\NetBankPaymentController;
use App\Http\Controllers\UnlimitPaymentController;
use App\Http\Controllers\UPIPaymentController;
use App\Http\Controllers\VDPaymentController;
use App\Http\Controllers\WoohooProcessingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Payment Gateway Routes
|--------------------------------------------------------------------------
| Initiation, return URLs, and webhook endpoints for all payment gateways.
*/

Route::post('/unlimit-payment', [UnlimitPaymentController::class, 'showPaymentForm'])->name('unlimit.form');

Route::middleware('throttle:payments')->group(function () {
    Route::match(['get', 'post'], '/unlimit/return', [WoohooProcessingController::class, 'handleReturn'])
        ->name('unlimit.return');
});

Route::get('/payment/success', function () {
    return Inertia::render('Checkout/Status', [
        'status' => 'success',
        'msg' => session('success') ?: 'Payment successful',
        'amount' => request()->query('amount'),
    ]);
})->name('payment.success');

Route::get('/payment/failed', function () {
    return Inertia::render('Checkout/Status', [
        'status' => 'failure',
        'msg' => 'Payment failed',
        'amount' => null,
    ]);
})->name('payment.failed');

Route::get('/payment/processed', function () {
    return Inertia::render('Checkout/Status', [
        'status' => 'success',
        'msg' => 'Payment processed',
        'amount' => null,
    ]);
})->name('payment.processed');

Route::get('/order-failure', function () {
    return Inertia::render('Checkout/Status', [
        'status' => 'failure',
        'msg' => 'Order could not be completed.',
        'amount' => null,
    ]);
});

Route::middleware(['auth', 'throttle:payments'])->group(function () {
    Route::post('/payment/upi', [UPIPaymentController::class, 'store'])->name('payment.upi');
    Route::post('/payment/netbnk', [NetBankPaymentController::class, 'store'])->name('payment.netbnk');
});

Route::middleware('throttle:payments')->group(function () {
    Route::match(['GET', 'POST'], '/upi/return', [UPIPaymentController::class, 'handleReturn'])->name('upi.return');
});

Route::get('/vd/payment/return', [VDPaymentController::class, 'handleReturnSuccess'])->name('vd.return');
Route::post('/vd-payment', [VDPaymentController::class, 'store'])->name('vd.payment');

Route::get('/kgen-payment/initiate', [KGenPaymentController::class, 'initiate'])->name('kgen.payment.initiate');
Route::get('/kgen-payment/success/{orderId}', [KGenPaymentController::class, 'handleReturnSuccess'])->name('kgen.payment.success');
Route::get('/kgen-payment/failed/{orderId}', [KGenPaymentController::class, 'handleReturnFailed'])->name('kgen.payment.failed');
