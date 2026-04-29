<?php

use App\Http\Controllers\Auth\UnifiedAuthController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| OTP-only mobile authentication.
*/

Route::post('logout', [UnifiedAuthController::class, 'logout'])->name('userLogOut');

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');
    Route::get('/unauthorized', fn () => Inertia::render('Error', [
        'status' => 401,
        'message' => 'You are not authorized to view this page.',
    ]))->name('unauthorized');

    Route::post('/auth/send-otp', [UnifiedAuthController::class, 'sendOtp'])
        ->middleware('throttle:10,1')
        ->name('auth.send-otp');
    Route::post('/auth/verify-otp', [UnifiedAuthController::class, 'verifyOtp'])
        ->middleware('throttle:30,1')
        ->name('auth.verify-otp');
    Route::post('/auth/complete-registration', [UnifiedAuthController::class, 'completeRegistration'])
        ->middleware('throttle:10,1')
        ->name('auth.complete-registration');
});
