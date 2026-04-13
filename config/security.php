<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VPN / Proxy Detection
    |--------------------------------------------------------------------------
    */
    'vpn_detection' => [
        'enabled' => env('VPN_DETECTION_ENABLED', true),

        // block | flag | log_only
        'action' => env('VPN_ACTION', 'block'),

        // Cache TTL for IP check results (seconds)
        'cache_ttl' => 86400,

        // Comma-separated IPs to always allow regardless of VPN status
        'bypass_ips' => array_filter(explode(',', env('VPN_BYPASS_IPS', ''))),

        // closed = block request if detection API is down (safer)
        // open   = allow request if detection API is down (not recommended)
        'fail_behavior' => 'closed',

        // Routes that skip VPN check entirely (health, static assets)
        'exempt_routes' => ['api/health', 'api/status', 'up'],

        // Per route-group overrides: 'block' | 'flag' | 'log_only' | null (use global)
        'route_groups' => [
            'api.orders' => 'block',
            'api.wallet' => 'block',
            'api.voucher-codes' => 'block',
            'api.catalog' => 'log_only',
            'admin' => 'flag',
            'api.reseller' => 'log_only',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IPHub API
    |--------------------------------------------------------------------------
    */
    'iphub' => [
        'api_key' => env('IPHUB_API_KEY'),
        'base_url' => 'https://v2.api.iphub.info/ip/',
        'timeout' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | GeoIP (MaxMind GeoLite2)
    |--------------------------------------------------------------------------
    */
    'geoip' => [
        'database_path' => storage_path('app/geoip/GeoLite2-City.mmdb'),
        'enabled' => env('GEOIP_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Geo Restrictions
    |--------------------------------------------------------------------------
    */
    'geo_restrictions' => [
        'enabled' => env('GEO_RESTRICT_ENABLED', false),
        'allowed_countries' => array_filter(explode(',', env('GEO_ALLOWED_COUNTRIES', 'IN'))),
        'action' => 'block',
    ],

    /*
    |--------------------------------------------------------------------------
    | Threat Detection & Auto-Blocking
    |--------------------------------------------------------------------------
    */
    'threat_detection' => [
        'enabled' => true,
        'failed_login_threshold' => 5,      // per hour per IP
        'request_rate_threshold' => 200,    // requests per minute per IP
        'auto_block_score' => 80,     // threat score to trigger auto-block
        'auto_block_duration' => 86400,  // seconds (24 hours)
        'permanent_block_after' => 3,      // auto-blocks before permanent block
        'admin_alert_on_block' => true,
        'challenge_method' => 'otp',  // otp | captcha
    ],

    /*
    |--------------------------------------------------------------------------
    | Transaction PIN
    |--------------------------------------------------------------------------
    */
    'transaction_pin' => [
        'enabled' => env('TRANSACTION_PIN_ENABLED', true),
        'length' => 6,
        'max_failed_attempts' => 5,
        'lockout_duration' => 1800,   // seconds (30 minutes)
        'required_for_b2c' => false,  // optional for B2C
        'required_for_b2b' => true,   // mandatory for B2B
        'required_above_amount' => 500,    // INR — require pin above this amount
    ],

    /*
    |--------------------------------------------------------------------------
    | Step-Up Authentication
    |--------------------------------------------------------------------------
    | Risk levels: 0=browse, 1=login, 2=pin, 3=pin+otp, 4=pin+otp+2fa
    */
    'step_up' => [
        'levels' => [
            0 => 'none',
            1 => 'session',
            2 => 'session_pin',
            3 => 'session_pin_otp',
            4 => 'session_pin_otp_2fa',
        ],
        'thresholds' => [
            'order_low' => 500,    // below this: level 1
            'order_medium' => 5000,   // below this: level 2
            'order_high' => 50000,  // below this: level 3; above: level 4
            'wallet_load' => 0,      // any amount: level 3
            'b2b_bulk_order' => 50000,  // above this: level 4
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cooling-Off Periods (in hours)
    |--------------------------------------------------------------------------
    */
    'cooling_off' => [
        'password_change' => 24,
        'email_change' => 48,
        'phone_change' => 48,
        'new_device' => 6,
        '2fa_disable' => 72,
        'api_key_regen' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Purchase Limits (defaults, can be overridden per user/tenant)
    |--------------------------------------------------------------------------
    */
    'purchase_limits' => [
        'b2c' => [
            'single_order_max' => 25000,    // INR
            'daily_max' => 50000,
            'monthly_max' => 200000,
            'orders_per_day' => 20,
            'wallet_load_daily' => 50000,
        ],
        'b2b' => [
            'single_order_max' => 500000,
            'daily_max' => 1000000,
            'monthly_max' => 50000000,
            'orders_per_day' => 200,
            'wallet_load_daily' => 2500000,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Voucher Code Protection
    |--------------------------------------------------------------------------
    */
    'voucher_protection' => [
        'max_views_per_hour' => 10,
        'one_time_view_enabled' => false,
        'access_window_hours' => 72,
        'require_pin_to_view' => true,
        'block_vpn_access' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Real-Time Security Alerts
    |--------------------------------------------------------------------------
    */
    'alerts' => [
        'slack_webhook_url' => env('SECURITY_SLACK_WEBHOOK_URL'),
        'alert_on_critical' => true,
        'alert_on_auto_block' => true,
        'repeated_critical_threshold' => 3,  // auto-block after N critical events/hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security
    |--------------------------------------------------------------------------
    */
    'session' => [
        'max_concurrent' => 3,       // max sessions per user
        'fingerprint_check' => true,    // verify user-agent + IP consistency
        'admin_lifetime' => 120,     // minutes
        'b2c_lifetime' => 1440,    // minutes (24h with remember-me)
    ],

    /*
    |--------------------------------------------------------------------------
    | Impossible Travel Detection
    |--------------------------------------------------------------------------
    */
    'impossible_travel' => [
        'enabled' => true,
        'max_speed_kmh' => 800,     // km/h — above this = impossible
        'action' => 'lock',  // lock | flag | alert
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Account Detection
    |--------------------------------------------------------------------------
    */
    'multi_account' => [
        'enabled' => true,
        'same_device_threshold' => 3,       // max accounts per device fingerprint
        'same_ip_threshold' => 5,       // max accounts per IP in 24h
    ],

    /*
    |--------------------------------------------------------------------------
    | Anti-Bot
    |--------------------------------------------------------------------------
    */
    'anti_bot' => [
        'recaptcha_enabled' => env('RECAPTCHA_ENABLED', false),
        'recaptcha_site_key' => env('RECAPTCHA_SITE_KEY'),
        'recaptcha_secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'recaptcha_score' => 0.5,     // minimum score (0-1)
        'honeypot_enabled' => true,
    ],

];
