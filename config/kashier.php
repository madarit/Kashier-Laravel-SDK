<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kashier Mode
    |--------------------------------------------------------------------------
    |
    | This option controls whether to use test or live Kashier credentials.
    | Supported: "test", "live"
    |
    */
    'mode' => env('KASHIER_MODE', 'test'),
    
    /*
    |--------------------------------------------------------------------------
    | Live Environment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for live payment processing.
    |
    */
    'live' => [
        'base_url' => 'https://checkout.kashier.io',
        'api_url' => 'https://api.kashier.io',
        'api_key' => env('KASHIER_LIVE_API_KEY', ''),
        'mid' => env('KASHIER_LIVE_MID', ''),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Test Environment Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for test payment processing.
    |
    */
    'test' => [
        'base_url' => 'https://checkout.kashier.io',
        'api_url' => 'https://test-api.kashier.io',
        'api_key' => env('KASHIER_TEST_API_KEY', '49c02cfa-8a4e-4120-8aa2-b154a6d08573'),
        'mid' => env('KASHIER_TEST_MID', 'MID-3552-454'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configure webhook endpoint for receiving payment notifications.
    |
    */
    'webhook' => [
        'enabled' => env('KASHIER_WEBHOOK_ENABLED', true),
        'prefix' => env('KASHIER_WEBHOOK_PREFIX', 'kashier'),
        // Your webhook URL will be: {APP_URL}/kashier/webhook
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Enable or disable logging of Kashier transactions and webhooks.
    |
    */
    'logging' => [
        'enabled' => env('KASHIER_LOGGING_ENABLED', true),
        'channel' => env('KASHIER_LOG_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Refund Configuration
    |--------------------------------------------------------------------------
    |
    | Configure refund behavior and settings.
    |
    */
    'refund' => [
        'auto_approve' => env('KASHIER_REFUND_AUTO_APPROVE', false),
        'timeout' => env('KASHIER_REFUND_TIMEOUT', 30), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Configuration
    |--------------------------------------------------------------------------
    |
    | Additional payment processing configurations.
    |
    */
    'payment' => [
        'default_currency' => env('KASHIER_CURRENCY', 'EGP'),
        'allowed_methods' => env('KASHIER_ALLOWED_METHODS', 'card,wallet,bank_installments'),
        'callback_url' => env('KASHIER_CALLBACK_URL', null),
    ],
];
