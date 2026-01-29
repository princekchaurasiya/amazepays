<?php

// Determine default API base URL based on environment
$appEnv = env('APP_ENV', 'production');
$defaultApiBaseUrl = (in_array($appEnv, ['local', 'testing'])) 
    ? 'https://sandbox.in.unlimit.com'
    : 'https://psp.in.unlimit.com';

return [
    'base_url' => env('UNLIMIT_BASE_URL', 'https://psp.in.unlimit.com/ma-new'),
    'login' => env('UNLIMIT_API_LOGIN'),
    'password' => env('UNLIMIT_API_PASSWORD'),
    
    // API Base URLs - environment aware
    // Set UNLIMIT_API_BASE_URL in .env to override auto-detection
    'api_base_url' => env('UNLIMIT_API_BASE_URL', $defaultApiBaseUrl),
    
    // Explicit sandbox and production URLs
    'api_base_url_sandbox' => env('UNLIMIT_API_BASE_URL_SANDBOX', 'https://sandbox.in.unlimit.com'),
    'api_base_url_production' => env('UNLIMIT_API_BASE_URL_PRODUCTION', 'https://psp.in.unlimit.com'),
    
    // API Endpoints
    'endpoints' => [
        'auth_token' => '/api/auth/token',
        'payments' => '/api/payments',
        'payment_request' => '/payment/request',
        'reports' => '/api/reports',
        'invoices' => '/api/invoices',
    ],
    
    // Timeout and Retry Configuration
    'timeout' => env('UNLIMIT_API_TIMEOUT', 15), // seconds
    'retry_attempts' => env('UNLIMIT_API_RETRY_ATTEMPTS', 2),
    'retry_delay' => env('UNLIMIT_API_RETRY_DELAY_MS', 1000), // milliseconds
];