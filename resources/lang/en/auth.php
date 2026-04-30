<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Laravel default auth lines
    |--------------------------------------------------------------------------
    */
    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    /*
    |--------------------------------------------------------------------------
    | AmazePays auth domain (OTP-only)
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'sent' => 'OTP sent successfully.',
        'send_failed' => 'Could not send OTP. Please try again.',
        'verified' => 'OTP verified successfully.',
    ],
    'login' => [
        'success' => 'Logged in successfully.',
    ],
    'profile' => [
        'required' => 'Profile completion required.',
        'completed' => 'Profile completed successfully.',
    ],
    'two_factor' => [
        'required' => 'Please complete 2FA verification.',
        'verified' => '2FA verified.',
    ],
    'session' => [
        'expired' => 'Session expired. Please verify your mobile again.',
    ],
    'account' => [
        'exists' => 'An account already exists for this number.',
        'blocked' => 'Your account has been restricted. Please contact support.',
        'locked' => 'Your account has been locked.',
    ],
    'logout' => [
        'success' => 'Logged out successfully.',
    ],

    'me' => 'User retrieved.',
];
