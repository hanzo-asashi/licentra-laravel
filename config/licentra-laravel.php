<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Licentra Server Base URL
    |--------------------------------------------------------------------------
    */
    'url' => env('LICENTRA_URL', 'https://licentra.test'),
    'server_url' => env('LICENTRA_URL', 'https://licentra.test'),
    'base_url' => env('LICENTRA_URL', 'https://licentra.test'),
    'activate_endpoint' => rtrim(env('LICENTRA_URL', 'https://licentra.test'), '/').'/api/license/activate',
    'ping_endpoint' => rtrim(env('LICENTRA_URL', 'https://licentra.test'), '/').'/api/license/ping',

    /*
    |--------------------------------------------------------------------------
    | Application License Key
    |--------------------------------------------------------------------------
    */
    'license_key' => env('LICENTRA_LICENSE_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Product Slug
    |--------------------------------------------------------------------------
    */
    'product_slug' => env('LICENTRA_PRODUCT_SLUG', 'aquanusa'),

    /*
    |--------------------------------------------------------------------------
    | Public Key RSA (For offline verification)
    |--------------------------------------------------------------------------
    */
    'public_key' => env('LICENTRA_PUBLIC_KEY'),
    'public_key_path' => env('LICENTRA_PUBLIC_KEY_PATH', storage_path('keys/licentra_public.pem')),
    'license_file_path' => env('LICENTRA_LICENSE_FILE_PATH', storage_path('app/license/license.lic')),
    'cache_key' => env('LICENTRA_CACHE_KEY', 'licentra_active_license'),
    'default_features' => [],
    'default_limits' => [],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    | TTL in seconds for caching ping & license validation status locally.
    */
    'cache_ttl' => env('LICENTRA_CACHE_TTL', 3600),

    /*
    |--------------------------------------------------------------------------
    | Offline Grace Period
    |--------------------------------------------------------------------------
    | Maximum days allowed to operate offline if server ping fails.
    */
    'grace_period_days' => env('LICENTRA_GRACE_PERIOD_DAYS', 3),

    /*
    |--------------------------------------------------------------------------
    | SSL Verification
    |--------------------------------------------------------------------------
    | Toggle HTTP SSL verification. Set to true in production.
    */
    'verify_ssl' => env('LICENTRA_VERIFY_SSL', false),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    | Enable/disable automatic webhook receiver route and customize route path.
    */
    'webhook' => [
        'enabled' => env('LICENTRA_WEBHOOK_ENABLED', true),
        'path' => env('LICENTRA_WEBHOOK_PATH', '/licentra/webhook'),
    ],
];
