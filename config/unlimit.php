<?php

/**
 * Unlimit — merchant area + API host mapping.
 *
 * Sandbox / production pairs come from .env (UNLIMIT_SANDBOX_* / UNLIMIT_PRODUCTION_*).
 * Active set: UNLIMIT_USE_SANDBOX=true|false, or when unset — local/testing → sandbox, else production.
 */
$appEnv = env('APP_ENV', 'production');
$explicitSandbox = env('UNLIMIT_USE_SANDBOX');
if ($explicitSandbox !== null && $explicitSandbox !== '') {
    $useSandbox = filter_var($explicitSandbox, FILTER_VALIDATE_BOOLEAN);
} else {
    $useSandbox = in_array($appEnv, ['local', 'testing'], true);
}

$sandbox = [
    'base_url' => env('UNLIMIT_SANDBOX_BASE_URL', 'https://sandbox.in.unlimit.com/ma-new'),
    'dashboard_url' => env('UNLIMIT_SANDBOX_DASHBOARD_URL', 'https://sandbox.in.unlimit.com/ma-new/#/dashboard'),
    'api_base_url' => env('UNLIMIT_SANDBOX_API_BASE_URL', 'https://sandbox.in.unlimit.com'),
    'login' => env('UNLIMIT_SANDBOX_API_LOGIN', env('UNLIMIT_API_LOGIN')),
    'password' => env('UNLIMIT_SANDBOX_API_PASSWORD', env('UNLIMIT_API_PASSWORD')),
];

$production = [
    'base_url' => env('UNLIMIT_PRODUCTION_BASE_URL', 'https://psp.in.unlimit.com/ma-new'),
    'dashboard_url' => env('UNLIMIT_PRODUCTION_DASHBOARD_URL', 'https://psp.in.unlimit.com/ma-new/'),
    'api_base_url' => env('UNLIMIT_PRODUCTION_API_BASE_URL', 'https://psp.in.unlimit.com'),
    'login' => env('UNLIMIT_PRODUCTION_API_LOGIN'),
    'password' => env('UNLIMIT_PRODUCTION_API_PASSWORD'),
];

$active = $useSandbox ? $sandbox : $production;

return [
    'use_sandbox' => $useSandbox,

    'sandbox' => $sandbox,
    'production' => $production,

    /** @deprecated Use sandbox.* / production.* or active keys; kept for older .env that only set UNLIMIT_BASE_URL */
    'base_url' => env('UNLIMIT_BASE_URL', $active['base_url']),
    'login' => $active['login'],
    'password' => $active['password'],

    'api_base_url' => env('UNLIMIT_API_BASE_URL', $active['api_base_url']),

    'api_base_url_sandbox' => env('UNLIMIT_API_BASE_URL_SANDBOX', $sandbox['api_base_url']),
    'api_base_url_production' => env('UNLIMIT_API_BASE_URL_PRODUCTION', $production['api_base_url']),

    'endpoints' => [
        'auth_token' => '/api/auth/token',
        'payments' => '/api/payments',
        'payment_request' => '/payment/request',
        'reports' => '/api/reports',
        'invoices' => '/api/invoices',
    ],

    'timeout' => env('UNLIMIT_API_TIMEOUT', 15),
    'retry_attempts' => env('UNLIMIT_API_RETRY_ATTEMPTS', 2),
    'retry_delay' => env('UNLIMIT_API_RETRY_DELAY_MS', 1000),
];
