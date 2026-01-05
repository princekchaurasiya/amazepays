<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\APIs\AuthenticationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AthenaGiftCardController;
use App\Http\Controllers\Api\ProductController;
// Route::get('signature-validation', [ApiController::class, 'signatureValidation']);

// Route::middleware('auth:sanctum')->group(function () {
//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     });
// });

Route::middleware('api')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('sendUserLoginOtp', [AuthenticationController::class, 'sendUserLoginOtp']);
        Route::post('verifyUserLoginOtp', [AuthenticationController::class, 'verifyUserLoginOtp']);

    });

    Route::prefix('products')->group(function () {
        Route::get('all', [AuthenticationController::class, 'allProduct']);
        Route::get('single', [AuthenticationController::class, 'singleProductDetails']);
    });

    Route::get('/orders', [AthenaGiftCardController::class, 'getOrder']);
    // Add more API routes as needed within the 'api' middleware group
});

Route::middleware('auth:sanctum')->get('/products', [ProductController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);   // All products
Route::get('/products/{id}', [ProductController::class, 'show']); // Single product

use App\Http\Controllers\UnlimitCallbackController;

Route::post('/unlimit/callback', [UnlimitCallbackController::class, 'handle'])
    ->middleware('verify.unlimit.signature');

//use App\Http\Controllers\UnlimitController;
 //Route::post('/unlimit/callback', [UnlimitController::class, 'callback'])->middleware('verify.unlimit.signature');

use App\Http\Controllers\WalletController;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/wallet/deposit', [WalletController::class, 'deposit']);
    Route::get('/wallet/balance', [WalletController::class, 'balance']);
    Route::get('/wallet/transactions', [WalletController::class, 'transactions']);
    Route::post('/wallet/debit', [WalletController::class, 'debit']);
});

use App\Http\Controllers\Admin\WalletTransactionController;
Route::middleware(['auth:sanctum', 'is.admin'])->group(function () {
    Route::get('/admin/wallet/transactions', [WalletTransactionController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'is.admin'])->get(
    '/admin/wallet/transactions/export',
    [WalletTransactionController::class, 'exportCsv']
);