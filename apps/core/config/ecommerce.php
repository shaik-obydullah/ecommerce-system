<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Application metadata
    |--------------------------------------------------------------------------
    |
    | Exposed by the version endpoint and useful for deploy verification.
    |
    */

    'version' => env('APP_VERSION', '0.1.0'),

    'commit' => env('GIT_COMMIT'),

    /*
    |--------------------------------------------------------------------------
    | Storefront mode
    |--------------------------------------------------------------------------
    |
    | "blade" registers the server-rendered storefront inside this application.
    | "headless" disables it; the Next.js storefront consumes /api/v1 instead.
    |
    */

    'storefront_mode' => env('STOREFRONT_MODE', 'blade'),

    /*
    |--------------------------------------------------------------------------
    | Store identity
    |--------------------------------------------------------------------------
    */

    'store_name' => env('STORE_NAME', env('APP_NAME', 'Ecommerce')),

    'store_timezone' => env('STORE_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Money
    |--------------------------------------------------------------------------
    |
    | Stock prices are stored in the base currency using integer minor units.
    | The customer-facing currency is resolved per request and validated against
    | the supported list. Never fall back silently to another currency.
    |
    */

    'base_currency' => env('BASE_CURRENCY', 'USD'),

    'default_currency' => env('DEFAULT_CURRENCY', env('BASE_CURRENCY', 'USD')),

    'supported_currencies' => array_values(array_filter(array_map(
        static fn (string $code): string => strtoupper(trim($code)),
        explode(',', (string) env('SUPPORTED_CURRENCIES', 'USD,EUR,GBP')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Currency metadata
    |--------------------------------------------------------------------------
    |
    | "minor_units" is the number of decimal places used by the currency.
    | All amounts are stored as integers in these minor units, so USD 12.34 is
    | stored as 1234 and JPY 1234 is stored as 1234.
    |
    */

    'currencies' => [
        'USD' => ['minor_units' => 2, 'symbol' => '$', 'symbol_position' => 'before', 'decimal_separator' => '.', 'thousands_separator' => ','],
        'EUR' => ['minor_units' => 2, 'symbol' => '€', 'symbol_position' => 'before', 'decimal_separator' => '.', 'thousands_separator' => ','],
        'GBP' => ['minor_units' => 2, 'symbol' => '£', 'symbol_position' => 'before', 'decimal_separator' => '.', 'thousands_separator' => ','],
        'CAD' => ['minor_units' => 2, 'symbol' => 'CA$', 'symbol_position' => 'before', 'decimal_separator' => '.', 'thousands_separator' => ','],
        'AUD' => ['minor_units' => 2, 'symbol' => 'A$', 'symbol_position' => 'before', 'decimal_separator' => '.', 'thousands_separator' => ','],
        'INR' => ['minor_units' => 2, 'symbol' => '₹', 'symbol_position' => 'before', 'decimal_separator' => '.', 'thousands_separator' => ','],
        'JPY' => ['minor_units' => 0, 'symbol' => '¥', 'symbol_position' => 'before', 'decimal_separator' => '.', 'thousands_separator' => ','],
        'KWD' => ['minor_units' => 3, 'symbol' => 'KD', 'symbol_position' => 'after', 'decimal_separator' => '.', 'thousands_separator' => ','],
    ],

    /*
    |--------------------------------------------------------------------------
    | Foreign exchange
    |--------------------------------------------------------------------------
    |
    | The latest rate for a currency pair is cached to keep catalog rendering
    | cheap. Rates are stored as exact decimals and converted with bcmath.
    |
    */

    'fx' => [
        'cache_ttl' => (int) env('FX_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cart
    |--------------------------------------------------------------------------
    |
    | Guest carts are identified by an opaque httpOnly cookie. The cart's
    | currency is locked at creation; switching currency starts a new cart.
    |
    */

    'cart' => [
        'cookie_lifetime_minutes' => (int) env('CART_COOKIE_LIFETIME_MINUTES', 43_200),
        'rate_limit_per_minute' => (int) env('CART_RATE_LIMIT_PER_MINUTE', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Initial admin account
    |--------------------------------------------------------------------------
    |
    | Used only by the admin seeder. Production deployments must set
    | ADMIN_PASSWORD explicitly.
    |
    */

    'admin' => [
        'name' => env('ADMIN_NAME', 'Store Admin'),
        'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        'password' => env('ADMIN_PASSWORD', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */

    'api' => [
        'version' => 'v1',
        'rate_limit_per_minute' => (int) env('API_RATE_LIMIT_PER_MINUTE', 60),
    ],

];
