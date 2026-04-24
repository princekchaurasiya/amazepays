<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lysto (Voucher Distributor)
    |--------------------------------------------------------------------------
    |
    | Base URLs
    | - UAT:  https://stagedistapi.lysto.io/api/v1
    | - Prod: https://rewards.lysto.io/api/v1
    |
    */

    'base_url' => env('LYSTO_BASE_URL', 'https://stagedistapi.lysto.io/api/v1'),
    'api_key' => env('LYSTO_API_KEY'),
    'partner_id' => env('LYSTO_PARTNER_ID'),
];

