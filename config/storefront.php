<?php

return [

    /*
    |--------------------------------------------------------------------------
    | B2B landing page — illustrative "saved to date" line (UI only)
    |--------------------------------------------------------------------------
    */
    'business_savings_display' => env('STOREFRONT_BUSINESS_SAVINGS_DISPLAY', 'Rs. 500'),

    /*
    |--------------------------------------------------------------------------
    | Checkout payment method toggles (UI + availability)
    |--------------------------------------------------------------------------
    |
    | Keep gateways behind backend toggles; do not expose unused gateways in UI.
    | Defaults to Razorpay only.
    */
    'checkout_payment_methods' => [
        'razorpay' => (bool) env('STOREFRONT_PAY_RAZORPAY', true),
        'ccavenue' => (bool) env('STOREFRONT_PAY_CCAVENUE', false),
        'unlimit' => (bool) env('STOREFRONT_PAY_UNLIMIT', false),
    ],

];
