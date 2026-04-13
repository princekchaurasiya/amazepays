<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default SMS Driver
    |--------------------------------------------------------------------------
    | Supported: "msg91", "digimiles", "twilio", "null"
    | Use "null" to disable SMS in development.
    */
    'default' => env('SMS_DRIVER', 'msg91'),

    /*
    |--------------------------------------------------------------------------
    | MSG91 (Recommended for India — DLT compliant)
    |--------------------------------------------------------------------------
    */
    'msg91' => [
        'auth_key' => env('MSG91_AUTH_KEY'),
        'sender_id' => env('MSG91_SENDER_ID', 'AMZPAY'),
        'route' => env('MSG91_ROUTE', '4'),   // 4 = Transactional
        'country' => env('MSG91_COUNTRY', '91'),
        'templates' => [
            'otp_login' => env('MSG91_OTP_TEMPLATE_ID'),
            'otp_transaction' => env('MSG91_OTP_TRANSACTION_TEMPLATE_ID'),
            'login_alert' => env('MSG91_LOGIN_ALERT_TEMPLATE_ID'),
            'wallet_credit' => env('MSG91_WALLET_CREDIT_TEMPLATE_ID'),
            'wallet_debit' => env('MSG91_WALLET_DEBIT_TEMPLATE_ID'),
            'security_alert' => env('MSG91_SECURITY_ALERT_TEMPLATE_ID'),
            'account_lock' => env('MSG91_ACCOUNT_LOCK_TEMPLATE_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Digimiles (Legacy — existing integration)
    |--------------------------------------------------------------------------
    */
    'digimiles' => [
        'api_url' => env('DIGIMILES_API_URL', 'https://bulksms.digimiles.in/bulksms/bulksmsapi'),
        'username' => env('DIGIMILES_USERNAME'),
        'password' => env('DIGIMILES_PASSWORD'),
        'sender_id' => env('DIGIMILES_SENDER_ID', 'AMZPAY'),
        'entity_id' => env('DIGIMILES_ENTITY_ID'),
        'template_id' => [
            'otp' => env('DIGIMILES_OTP_TEMPLATE_ID'),
            'gift_code' => env('DIGIMILES_GIFT_TEMPLATE_ID'),
            'transaction' => env('DIGIMILES_TRANSACTION_TEMPLATE_ID'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Twilio (International fallback)
    |--------------------------------------------------------------------------
    */
    'twilio' => [
        'sid' => env('TWILIO_SID'),
        'token' => env('TWILIO_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    */
    'otp' => [
        'length' => 6,
        'type' => 'numeric',  // numeric | alphanumeric
        'validity_minutes' => 5,
        'max_attempts' => 5,
        'rate_limit' => [
            'per_phone' => 3,          // max OTPs per phone number
            'window' => 600,        // in N seconds (10 minutes)
        ],
        'resend_cooldown' => 60,         // seconds before resend is allowed
    ],

];
