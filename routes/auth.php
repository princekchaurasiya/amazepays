<?php

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\UnifiedAuthController;
use App\Http\Controllers\ChangePasswordUpdateController;
use App\Http\Controllers\OtpVerificationController;
use App\Http\Controllers\SmsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Guest, login, registration, OTP, and email verification flows.
*/

Route::post('logout', [LoginController::class, 'logout'])->name('userLogOut');

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');
    Route::get('/register', fn () => redirect()->route('login'))->name('register');
    Route::get('/forgot-password', fn () => redirect()->route('login'))->name('forgot-password');
    Route::post('/user-registration', [RegisterController::class, 'register'])->name('user-registration');
    Route::post('/user-login', [LoginController::class, 'login'])->name('user-login');
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

Route::get('/verify-email', [EmailVerificationController::class, 'showVerifyForm'])->name('verify.email');
Route::post('/verify-email', [EmailVerificationController::class, 'verifyEmail']);
Route::post('/resend-verification', [EmailVerificationController::class, 'resendVerification'])->name('resend.verification');

Route::post('/send-sms', [SmsController::class, 'loginWithOtp'])
    ->middleware('throttle:5,1')
    ->name('send-sms');
Route::post('/register-otp', [SmsController::class, 'registerWithOtp'])
    ->middleware('throttle:5,1')
    ->name('send-register-otp');
Route::post('/forget-password-send-otp', [SmsController::class, 'forgetPasswordWithMobileOtp'])
    ->middleware('throttle:5,1')
    ->name('send-forgot-password-otp');
Route::post('/user-forgot-password', [PasswordResetController::class, 'reset'])->name('user-forgot-password');
Route::post('/verify-otp', [OtpVerificationController::class, 'loginVerifyOtp'])->name('verify-otp');
Route::post('/verify-register-otp', [OtpVerificationController::class, 'registerVerifyOtp'])->name('verify-register-otp');

Route::middleware('auth')->group(function () {
    Route::post('/change-password-update', [ChangePasswordUpdateController::class, 'updatePassword'])->name('password-change');
    Route::post('/user-logout', [LoginController::class, 'logout'])->name('user-logout');
    Route::get('/change-password', fn () => Inertia::render('Auth/ChangePassword'))->name('change-password');
});

Route::get('/unauthenticated', function () {
    $message = session('message', 'You are not authenticated.');

    return redirect()->route('error', ['message' => $message]);
})->name('unauthenticated');
