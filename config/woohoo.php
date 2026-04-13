<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Woohoo API host (no scheme)
    |--------------------------------------------------------------------------
    | e.g. sandbox.woohoo.in — code builds URLs as https://{host}/rest/v3/...
    */
    'host' => env('WOOHOO_URL', ''),

    'client_id' => env('WOOHOO_CLIENT_ID', ''),
    'client_secret' => env('WOOHOO_CLIENT_SECRET', ''),
    'bearer_token' => env('WOOHOO_BEARER_TOKEN', ''),
    'username' => env('WOOHOO_USERNAME', ''),
    'password' => env('WOOHOO_PASSWORD', ''),
];
