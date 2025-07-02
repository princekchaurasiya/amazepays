<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\APIs\AuthenticationController;
use Illuminate\Support\Facades\Route;
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

    // Add more API routes as needed within the 'api' middleware group
});
