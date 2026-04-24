<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Voucher distributors (CatalogSyncService::syncAll)
    |--------------------------------------------------------------------------
    */
    'providers' => [
        'woohoo',
        'kgen',
        'value_design',
        'lysto',
        'vouchagram_send',
        'vouchagram_pull',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default product audiences by distributor
    |--------------------------------------------------------------------------
    |
    | Used by catalog sync to decide whether imported products should be visible
    | to B2C, B2B, or both. (Values are strings to keep config simple.)
    |
    | Allowed: 'b2c', 'b2b', 'both'
    |
    */
    'default_audiences' => [
        'value_design' => env('VOUCHER_DEFAULT_AUDIENCE_VALUE_DESIGN', 'both'),
        'kgen' => env('VOUCHER_DEFAULT_AUDIENCE_KGEN', 'b2b'),
        'lysto' => env('VOUCHER_DEFAULT_AUDIENCE_LYSTO', 'b2b'),
        'woohoo' => env('VOUCHER_DEFAULT_AUDIENCE_WOOHOO', 'b2c'),
        'vouchagram_send' => env('VOUCHER_DEFAULT_AUDIENCE_VOUCHAGRAM_SEND', 'b2c'),
        'vouchagram_pull' => env('VOUCHER_DEFAULT_AUDIENCE_VOUCHAGRAM_PULL', 'b2b'),
    ],
];

