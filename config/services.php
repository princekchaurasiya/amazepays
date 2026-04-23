<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'vdweb' => [
        'username' => env('VDWEB_USERNAME'),
        'password' => env('VDWEB_PASSWORD'),
        'distributor_id' => env('VDWEB_DISTRIBUTOR_ID'),
    ],

    'value_design' => [
        'base_url' => env('VALUE_DESIGN_BASE_URL', 'http://cards.vdwebapi.com/distributor/'),
        'username' => env('VALUE_DESIGN_USERNAME', env('VDWEB_USERNAME')),
        'password' => env('VALUE_DESIGN_PASSWORD', env('VDWEB_PASSWORD')),
        'distributor_id' => env('VALUE_DESIGN_DISTRIBUTOR_ID', env('VDWEB_DISTRIBUTOR_ID')),
        'secret_key' => env('VALUE_DESIGN_SECRET_KEY', env('AES_SECRET_KEY')),
        'secret_iv' => env('VALUE_DESIGN_SECRET_IV', env('AES_IV')),
    ],

    'giftcard' => [
        'secret' => env('LYSTO_API_KEY'),
    ],

    'unlimit' => [
        'callback_secret' => env('UNLIMIT_CALLBACK_SECRET'),
    ],

    /*
    | Providers included in voucher:sync-catalog (CatalogSyncService::syncAll)
    */
    'voucher_providers' => [
        'woohoo',
        'kgen',
        'value_design',
        'vouchagram_send',
        'vouchagram_pull',
    ],

];
