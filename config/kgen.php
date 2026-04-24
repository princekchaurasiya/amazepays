<?php

return [
    /*
    |--------------------------------------------------------------------------
    | KGen / EXLR8 (Voucher Distributor)
    |--------------------------------------------------------------------------
    |
    | UAT Base URL example:
    |   https://stage-exlr8-api-gateway.exlr8now.com
    |
    | API prefix:
    |   /api/v1/delivery-partners
    |
    */

    'base_url' => env('EXLR8_BASE_URL', 'https://stage-exlr8-api-gateway.exlr8now.com'),
    'api_prefix' => env('EXLR8_API_PREFIX', '/api/v1/delivery-partners'),

    // Base64 or raw (match what upstream expects)
    'user_id' => env('EXLR8_USER_ID'),
    'user_secret' => env('EXLR8_USER_SECRET'),

    // Delivery partner ID (dpID)
    'dp_id' => env('EXLR8_DP_ID'),
];

