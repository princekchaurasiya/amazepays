<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\UnlimitController;
use App\Http\Controllers\UnlimitPaymentController;
use App\Http\Controllers\VDPageController;
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
    Route::post('/update-session-data', [ProductPageController::class, 'updateSessionData'])->name('updateSessionData');

    Route::get('/checkout/{slug}', [ProductPageController::class, 'storePayNowData'])->name('checkoutPage');
    Route::post('/checkout/{slug}', [ProductPageController::class, 'storePayNowData'])->name('checkoutPage.post');

    Route::get('/cart', [ProductPageController::class, 'showCart'])->name('storefront.cart');
    Route::post('/cart/remove', [ProductPageController::class, 'removeFromCart'])->name('storefront.cart.remove');
    Route::post('/cart/clear', [ProductPageController::class, 'clearCart'])->name('storefront.cart.clear');
    Route::post('/cart/{slug}', [ProductPageController::class, 'addToCart'])->name('storefront.cart.add');

    Route::post('/payment-process', [UnlimitPaymentController::class, 'store'])->name('unlimit.store');
    Route::post('/save-gift-card-form', [CheckoutController::class, 'saveGiftCardForm'])->name('save-gift-card-form');

    Route::post('/store-billing-data', [BillingController::class, 'store'])->name('storeBillingData');

    Route::post('/woohoo/create-order', [WoohooOrderController::class, 'createOrder'])->name('woohoo.createOrder');
    Route::get('/woohoo/check-status', [WoohooOrderController::class, 'checkTransactionStatus'])->name('woohoo.checkStatus');
    Route::post('/woohoo/clear-session', [WoohooOrderController::class, 'clearSessionData'])->name('woohoo.clearSession');

    Route::get('/woohoo/processing', [WoohooProcessingController::class, 'showProcessing'])->name('woohoo.processing');
    Route::get('/woohoo/processing/create-order', [WoohooProcessingController::class, 'createOrder'])->name('woohoo.processing.createOrder');
    Route::post('/woohoo/processing/create-order', [WoohooProcessingController::class, 'createOrder'])->name('woohoo.processing.createOrder.post');
    Route::get('/woohoo/process', [WoohooProcessingController::class, 'createOrder'])->name('woohoo.process');

    Route::post('/vd-update-session-data', [VDPageController::class, 'updateSessionData'])->name('vdupdateSessionData');
    Route::match(['get', 'post'], '/vd-checkout', [VDPageController::class, 'storePayNowData'])->name('vdcheckoutPage');
});

Route::middleware('auth')->group(function () {
    Route::post('/unlimit/checkout', [UnlimitController::class, 'checkout'])->name('unlimit.checkout');
    Route::get('/unlimit/status/{merchant_order_id}', [UnlimitController::class, 'checkTransactionStatus'])->name('unlimit.status');
});
