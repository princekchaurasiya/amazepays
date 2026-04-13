<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Vouchagram Send Voucher (B2C) — Communication Engine
    |--------------------------------------------------------------------------
    */
    'send' => [
        'url' => rtrim((string) env('VOUCHAGRAM_SEND_URL', 'https://send.bulkgv.net/API/v1'), '/'),
        'username' => env('VOUCHAGRAM_SEND_USERNAME'),
        'password' => env('VOUCHAGRAM_SEND_PASSWORD'),
        'key' => env('VOUCHAGRAM_SEND_KEY'),
        'iv' => env('VOUCHAGRAM_SEND_IV'),
        'communication_mode' => env('VOUCHAGRAM_SEND_COMMUNICATION_MODE', '5'),
        'template_id' => env('VOUCHAGRAM_SEND_TEMPLATE_ID', '213'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Vouchagram Pull Voucher (B2B)
    |--------------------------------------------------------------------------
    */
    'pull' => [
        'url' => rtrim((string) env('VOUCHAGRAM_PULL_URL', 'https://send.bulkgv.net/API/v1'), '/'),
        'username' => env('VOUCHAGRAM_PULL_USERNAME'),
        'password' => env('VOUCHAGRAM_PULL_PASSWORD'),
        'key' => env('VOUCHAGRAM_PULL_KEY'),
        'iv' => env('VOUCHAGRAM_PULL_IV'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token cache TTL (seconds) — JWT expires in 30 minutes
    |--------------------------------------------------------------------------
    */
    'token_cache_ttl' => (int) env('VOUCHAGRAM_TOKEN_CACHE_TTL', 1500),

    /*
    |--------------------------------------------------------------------------
    | TLS verification for Guzzle (Windows / XAMPP web SAPI)
    |--------------------------------------------------------------------------
    | If you see cURL error 60, set this to cacert.pem or rely on auto-detection:
    | VOUCHAGRAM_HTTP_CA_BUNDLE, SSL_CERT_FILE, CURL_CA_BUNDLE, php.ini curl.cainfo /
    | openssl.cafile, PHP_BINARY/../extras/ssl/cacert.pem, or C:\xampp\php\extras\ssl\cacert.pem.
    */
    'http_ca_bundle' => env('VOUCHAGRAM_HTTP_CA_BUNDLE'),

];
