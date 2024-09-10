<?php

return [
    'merchant_id' => env('CCAVENUE_MERCHANT_ID'),
    'access_code' => env('CCAVENUE_ACCESS_CODE'),
    'working_key' => env('CCAVENUE_WORKING_KEY'),
    'ccavenue_api_endpoint' => env('CCAVENUE_LINK'),

    // Static values for payment
    'numeric_code' => env('CCAVENUE_NUMERIC_CODE', '356'),
    'currency' => env('CCAVENUE_CURRENCY', 'INR'),
    'language' => env('CCAVENUE_LANGUAGE', 'EN'),
];
