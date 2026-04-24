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
    | AmazePays API auth lines (mobile / api/v1)
    |--------------------------------------------------------------------------
    */
    'validation_failed' => 'Validation failed.',
    'otp_sent' => 'OTP sent successfully.',
    'otp_send_failed' => 'Could not send OTP. Please try again.',
    'logged_in' => 'Logged in successfully.',
    'needs_profile' => 'Profile completion required.',
    'two_factor_required' => 'Please complete 2FA verification.',
    'registration_successful' => 'Registration successful.',
    'two_factor_verified' => '2FA verified.',
    'logged_out' => 'Logged out successfully.',
    'me' => 'User retrieved.',
    'session_expired' => 'Session expired. Please verify your mobile again.',
    'account_exists' => 'An account already exists for this number.',
    'account_blocked' => 'Your account has been restricted. Please contact support.',
    'account_locked' => 'Your account has been locked.',
];
