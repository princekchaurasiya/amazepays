<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CheckoutSessionController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\PaymentSessionController;
use App\Http\Controllers\Api\V1\TransactionPinController;
use App\Http\Controllers\Api\V1\WalletController;
use App\Http\Controllers\Payment\PaymentCallbackController;
use App\Http\Controllers\Voucher\WoohooCallbackController;
use App\Support\Http\ResponsePayload;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — AmazePays v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('api.v1.')->group(function () {

    // Health check (no auth)
    Route::get('/health', fn () => ResponsePayload::ok(null, ['status' => 'ok', 'version' => '1.0']));

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
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::prefix('catalog')->name('catalog.')->group(function () {
        Route::get('/', [CatalogController::class, 'index'])->name('index');
        Route::get('/categories', [CatalogController::class, 'categories'])->name('categories');
        Route::get('/{product}', [CatalogController::class, 'show'])->name('show');
    });

    /*
    |--------------------------------------------------------------------------
    | Authenticated Consumer Routes (B2C)
    |--------------------------------------------------------------------------
    | Custom middleware used in this group:
    |   cooling.off      — prevents sensitive actions for a short window after
    |                      a security-sensitive change (e.g. password/email change).
    |                      Accepts a 'reason' param: 'password_change', 'email_change'.
    |   step.up          — requires the user to re-authenticate to a higher assurance
    |                      level before proceeding. Accepts a numeric level (2 or 3).
    |   transaction.pin  — verifies the user's 4-digit transaction PIN before allowing
    |                      wallet loads or voucher code reveals.
    |   purchase.limits  — enforces per-user and per-product daily/monthly order caps.
    |   vpn.check        — rejects order placements from known VPN/proxy IP ranges.
    */
    Route::middleware(['auth:sanctum', 'cooling.off:password_change'])->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('/auth/me', [AuthController::class, 'me'])->name('auth.me');

        // Checkout sessions (draft order creation)
        Route::prefix('checkout')->name('checkout.')->group(function () {
            Route::post('/sessions', [CheckoutSessionController::class, 'create'])->name('sessions.create')
                ->middleware('idempotency:api.v1.checkout.sessions.create');
        });

        // Payment sessions (initiate payment for an order)
        Route::prefix('payments')->name('payments.')->group(function () {
            Route::post('/sessions', [PaymentSessionController::class, 'create'])->name('sessions.create')
                ->middleware('idempotency:api.v1.payments.sessions.create');
        });

        // Wallet
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/balance', [WalletController::class, 'balance'])->name('balance');
            Route::get('/transactions', [WalletController::class, 'transactions'])->name('transactions');
            Route::post('/load-request', [WalletController::class, 'requestLoad'])->name('load.request')
                ->middleware(['idempotency:api.v1.wallet.load.request', 'step.up:3', 'transaction.pin', 'cooling.off:email_change']);
            Route::get('/load-request/{loadRequest}', [WalletController::class, 'loadRequestStatus'])->name('load.status');
        });

        // Orders
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [OrderController::class, 'index'])->name('index');
            Route::get('/{order}', [OrderController::class, 'show'])->name('show');
            Route::post('/', [OrderController::class, 'placeOrder'])->name('store')
                ->middleware(['idempotency:api.v1.orders.store', 'purchase.limits', 'vpn.check']);
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
        Route::post('/orders', [OrderController::class, 'placeOrder'])->name('orders.store')
            ->middleware('idempotency:api.v1.reseller.orders.store');
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
        Route::post('/orders', [OrderController::class, 'placeOrder'])->name('orders.store')
            ->middleware('idempotency:api.v1.loyalty.orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    });

    /*
    |--------------------------------------------------------------------------
    | Payment Webhooks (no auth — signature verified by specific middleware)
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Payment Webhooks (no auth — signature verified by specific middleware)
    |--------------------------------------------------------------------------
    | Every gateway callback MUST have its own signature-verification middleware.
    | Never accept a payment callback without verifying the HMAC/checksum first:
    |   - CCAvenue  → verify.ccavenue.signature  (MD5 checksum via working key)
    |   - Unlimit   → verify.unlimit.signature   (HMAC-SHA256 via Signature header)
    |   - Razorpay  → verify.razorpay.signature  (HMAC-SHA256 via X-Razorpay-Signature)
    |   - Woohoo    → verify.woohoo.signature    (as per Woohoo webhook docs)
    |
    | withoutMiddleware(['auth:sanctum']) is intentional — these are server-to-server
    | callbacks from payment gateways, not user sessions. Removing this line would
    | cause all gateway callbacks to receive 401 Unauthorized responses.
    |
    | Middleware stubs to create in app/Http/Middleware/:
    |   VerifyCCAvenuSignature.php
    |   VerifyRazorpaySignature.php
    |   VerifyWoohooSignature.php  (if not already present)
    | Register each in app/Http/Kernel.php under $routeMiddleware.
    */
    Route::prefix('webhooks')->name('webhooks.')->withoutMiddleware(['auth:sanctum'])->group(function () {
        Route::post('/ccavenue', [PaymentCallbackController::class, 'ccavenue'])
            ->name('ccavenue')
            ->middleware('verify.ccavenue.signature');

        Route::post('/unlimit', [PaymentCallbackController::class, 'unlimit'])
            ->name('unlimit')
            ->middleware('verify.unlimit.signature');

        Route::post('/razorpay', [PaymentCallbackController::class, 'razorpay'])
            ->name('razorpay')
            ->middleware('verify.razorpay.signature');

        Route::post('/woohoo', [WoohooCallbackController::class, 'handle'])
            ->name('woohoo')
            ->middleware('verify.woohoo.signature');
    });
});
