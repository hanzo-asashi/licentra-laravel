<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Licentra Server Base URL
    |--------------------------------------------------------------------------
    */
    'url' => env('LICENTRA_URL', 'https://licentra.test'),

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
];
