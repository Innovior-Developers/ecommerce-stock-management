<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Specify the default payment gateway to use
    |
    */
    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'stripe'),

    /*
    |--------------------------------------------------------------------------
    | Payment Currency Configuration
    |--------------------------------------------------------------------------
    */
    'default_currency' => env('PAYMENT_DEFAULT_CURRENCY', 'USD'),

    'allowed_currencies' => [
        'USD', // United States Dollar
        'EUR', // Euro
        'LKR', // Sri Lankan Rupee
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency to Gateway Mapping
    |--------------------------------------------------------------------------
    |
    | Map currencies to preferred gateways
    |
    */
    'currency_gateway_map' => [
        'LKR' => 'payhere',
        'USD' => 'stripe',
        'EUR' => 'stripe',
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Timeout Configuration
    |--------------------------------------------------------------------------
    */
    'payment_timeout' => env('PAYMENT_TIMEOUT', 1800), // 30 minutes in seconds

    /*
    |--------------------------------------------------------------------------
    | Auto-Capture Configuration
    |--------------------------------------------------------------------------
    |
    | Whether to automatically capture payments or require manual capture
    |
    */
    'auto_capture' => env('PAYMENT_AUTO_CAPTURE', true),

    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    */
    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'api_version' => '2023-10-16',
    ],

    /*
    |--------------------------------------------------------------------------
    | PayPal Configuration
    |--------------------------------------------------------------------------
    */
    'paypal' => [
        'mode' => env('PAYPAL_MODE', 'sandbox'), // sandbox or live
        'sandbox' => [
            'client_id' => env('PAYPAL_SANDBOX_CLIENT_ID'),
            'client_secret' => env('PAYPAL_SANDBOX_SECRET'),
        ],
        'live' => [
            'client_id' => env('PAYPAL_LIVE_CLIENT_ID'),
            'client_secret' => env('PAYPAL_LIVE_SECRET'),
        ],
        'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
        'return_url' => env('PAYPAL_RETURN_URL', 'http://localhost:3000/payment/paypal/return'),
        'cancel_url' => env('PAYPAL_CANCEL_URL', 'http://localhost:3000/payment/paypal/cancel'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PayHere Configuration
    |--------------------------------------------------------------------------
    */
    'payhere' => [
        'merchant_id' => env('PAYHERE_MERCHANT_ID'),
        'merchant_secret' => env('PAYHERE_MERCHANT_SECRET'),
        'mode' => env('PAYHERE_MODE', 'sandbox'), // sandbox or live
        'return_url' => env('PAYHERE_RETURN_URL', 'http://localhost:3000/payment/payhere/return'),
        'cancel_url' => env('PAYHERE_CANCEL_URL', 'http://localhost:3000/payment/payhere/cancel'),
        'notify_url' => env('PAYHERE_NOTIFY_URL', 'http://localhost:8000/api/webhooks/payhere'),
    ],
];