<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\TransactionPinController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Payment\CCAvenuCallbackController;
use App\Http\Controllers\Payment\RazorpayCallbackController;
use App\Http\Controllers\Payment\UnlimitCallbackController;
use App\Http\Controllers\Voucher\WoohooCallbackController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — AmazePays v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Health check (no auth)
    Route::get('/health', fn () => response()->json(['status' => 'ok', 'version' => '1.0']));

    /*
    |--------------------------------------------------------------------------
    | Public Auth Routes
    |--------------------------------------------------------------------------
    */
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('/otp/send', [AuthController::class, 'sendOtp'])->name('otp.send')
            ->middleware('throttle:3,1');
        Route::post('/otp/verify', [AuthController::class, 'verifyOtp'])->name('otp.verify')
            ->middleware('throttle:20,1');
        Route::post('/complete-profile', [AuthController::class, 'completeProfile'])->name('complete.profile')
            ->middleware('throttle:20,1');
        Route::post('/2fa/verify', [AuthController::class, 'verifyTwoFactor'])->name('2fa.verify')
            ->middleware('auth:sanctum');
    });

    /*
    |--------------------------------------------------------------------------
    | Public Catalog (B2C — browsing allowed without login)
    |--------------------------------------------------------------------------
    */
    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', [CatalogController::class, 'index'])->name('index');
        Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
        Route::get('/{product}', [CatalogController::class, 'show'])->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | Authenticated Consumer Routes (B2C)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'cooling.off:password_change'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        // Wallet
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/balance', [WalletController::class, 'balance'])->name('balance');
            Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
            Route::post('/load-request', [WalletController::class, 'requestLoad'])->name('load.request')
                ->middleware(['step.up:3', 'transaction.pin', 'cooling.off:email_change']);
            Route::get('/load-request/{loadRequest}', [WalletController::class, 'loadRequestStatus'])->name('load.status');
        });

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/{order}', [OrderController::class, 'show'])->name('show');
            Route::post('/', [OrderController::class, 'placeOrder'])->name('store')
                ->middleware(['purchase.limits', 'vpn.check']);
            Route::get('/{order}/voucher-code', [OrderController::class, 'getVoucherCode'])->name('voucher')
                ->middleware(['step.up:2', 'transaction.pin']);
        });

        // Transaction PIN management
        Route::prefix('transaction-pin')->name('transaction-pin.')->group(function () {
            Route::post('/set', [TransactionPinController::class, 'set'])->name('set');
            Route::post('/change', [TransactionPinController::class, 'change'])->name('change');
            Route::post('/verify', [TransactionPinController::class, 'verify'])->name('verify');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Reseller API (HMAC-SHA256 auth)
    |--------------------------------------------------------------------------
    */
    Route::prefix('reseller')->name('reseller.')->middleware(['api.key', 'ip.whitelist', 'tenant'])->group(function () {
        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
        Route::post('/orders', [OrderController::class, 'placeOrder'])->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/wallet/balance', [WalletController::class, 'balance'])->name('wallet.balance');
    });

    /*
    |--------------------------------------------------------------------------
    | Loyalty Program API (OAuth2 Client Credentials)
    |--------------------------------------------------------------------------
    */
    Route::prefix('loyalty')->name('loyalty.')->middleware(['auth:api', 'tenant'])->group(function () {
        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
        Route::post('/orders', [OrderController::class, 'placeOrder'])->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Payment Webhooks (no auth — signature verified by specific middleware)
    |--------------------------------------------------------------------------
    */
    Route::prefix('webhooks')->name('webhooks.')->withoutMiddleware(['auth:sanctum'])->group(function () {
        Route::post('/ccavenue', [CCAvenuCallbackController::class, 'handle'])->name('ccavenue');
        Route::post('/unlimit', [UnlimitCallbackController::class, 'handle'])
            ->name('unlimit')
            ->middleware('verify.unlimit.signature');
        Route::post('/razorpay', [RazorpayCallbackController::class, 'handle'])->name('razorpay');
        Route::post('/woohoo', [WoohooCallbackController::class, 'handle'])->name('woohoo');
    });
});
