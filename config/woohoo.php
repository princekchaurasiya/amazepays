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
    // Single source of truth: `settings` table (encrypted with WOOHOO_TOKEN_ENCRYPTION_KEY).
    // This key is hydrated into config at runtime by `AppServiceProvider`.
    'bearer_token' => '',
    'username' => env('WOOHOO_USERNAME', ''),
    'password' => env('WOOHOO_PASSWORD', ''),
];
