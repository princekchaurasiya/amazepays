<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\Storefront\CheckoutSessionController;
use App\Http\Controllers\Storefront\StorefrontProductController;
use App\Http\Controllers\UnlimitController;
use App\Http\Controllers\Voucher\ValueDesignCheckoutController;
use App\Http\Controllers\WoohooOrderController;
use App\Http\Controllers\WoohooProcessingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Checkout & Order Routes
|--------------------------------------------------------------------------
| Checkout flows, order creation, and order processing for all providers.
*/

Route::middleware(['auth', 'check.transaction'])->group(function () {
    Route::post('/checkout/session/billing', [CheckoutSessionController::class, 'updateBillingDetails'])
        ->name('checkout.session.billing.update');

    Route::get('/checkout/{slug}', [StorefrontProductController::class, 'showCheckout'])->name('checkoutPage');
    Route::post('/checkout/{slug}', [CheckoutSessionController::class, 'submitCheckout'])->name('checkoutPage.post');

    Route::get('/cart', [StorefrontProductController::class, 'showCart'])->name('storefront.cart');
    Route::post('/cart/remove', [CheckoutSessionController::class, 'removeFromCart'])->name('storefront.cart.remove');
    Route::post('/cart/clear', [CheckoutSessionController::class, 'clearCart'])->name('storefront.cart.clear');
    Route::post('/cart/{slug}', [CheckoutSessionController::class, 'addToCart'])->name('storefront.cart.add');

    // Legacy alias (Phase 4): preserve endpoint for one release; redirect to canonical route.
    Route::post('/payment-process', function () {
        return redirect()->route('payment.unlimit', [], 308);
    })->name('unlimit.store');
    Route::post('/checkout/session/gift-draft', [CheckoutSessionController::class, 'saveGiftCheckoutDraft'])
        ->name('checkout.session.gift_draft.save');

    Route::post('/store-billing-data', [BillingController::class, 'store'])->name('storeBillingData');

    Route::post('/woohoo/create-order', [WoohooOrderController::class, 'createOrder'])->name('woohoo.createOrder');
    Route::get('/woohoo/check-status', [WoohooOrderController::class, 'checkTransactionStatus'])->name('woohoo.checkStatus');
    Route::post('/woohoo/clear-session', [WoohooOrderController::class, 'clearSessionData'])->name('woohoo.clearSession');

    Route::get('/woohoo/processing', [WoohooProcessingController::class, 'showProcessing'])->name('woohoo.processing');
    Route::get('/woohoo/processing/create-order', [WoohooProcessingController::class, 'createOrder'])->name('woohoo.processing.createOrder');
    Route::post('/woohoo/processing/create-order', [WoohooProcessingController::class, 'createOrder'])->name('woohoo.processing.createOrder.post');
    Route::get('/woohoo/process', [WoohooProcessingController::class, 'createOrder'])->name('woohoo.process');

    Route::post('/vd/checkout/session', [ValueDesignCheckoutController::class, 'updateSession'])
        ->name('vd.checkout.session.update');
    Route::post('/vd-checkout', [ValueDesignCheckoutController::class, 'submit'])->name('vdcheckoutPage');
});

Route::middleware('auth')->group(function () {
    Route::post('/unlimit/checkout', [UnlimitController::class, 'checkout'])->name('unlimit.checkout');
    Route::get('/unlimit/status/{merchant_order_id}', [UnlimitController::class, 'checkTransactionStatus'])->name('unlimit.status');
});
