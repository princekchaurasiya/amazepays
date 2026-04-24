<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Value Design (Voucher Distributor)
    |--------------------------------------------------------------------------
    |
    | Base URLs
    | - UAT:  http://cards.vdwebapi.com/distributor/
    | - Prod: https://at.valuedesign.co.in/distributor/
    |
    */

    'environment' => env('VALUE_DESIGN_ENV', 'uat'),

    'base_url' => env('VALUE_DESIGN_BASE_URL', 'http://cards.vdwebapi.com/distributor/'),

    'username' => env('VALUE_DESIGN_USERNAME'),
    'password' => env('VALUE_DESIGN_PASSWORD'),

    'distributor_id' => env('VALUE_DESIGN_DISTRIBUTOR_ID'),

    'secret_key' => env('VALUE_DESIGN_SECRET_KEY'),
    'iv' => env('VALUE_DESIGN_IV'),
];

