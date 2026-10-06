<?php

/*
|--------------------------------------------------------------------------
| SaaS billing provider
|--------------------------------------------------------------------------
|
| Paddle Billing (merchant of record) is the launch provider. Client project
| money never flows through this platform: this config is only for the
| SaaS subscription itself.
|
*/

return [
    'provider' => env('BILLING_PROVIDER', 'paddle'),

    'paddle' => [
        'sandbox' => (bool) env('PADDLE_SANDBOX', true),
        'api_key' => env('PADDLE_API_KEY'),
        'client_side_token' => env('PADDLE_CLIENT_SIDE_TOKEN'),
        'webhook_secret' => env('PADDLE_WEBHOOK_SECRET'),
        // Maximum age of a webhook signature timestamp, in seconds.
        'webhook_tolerance' => (int) env('PADDLE_WEBHOOK_TOLERANCE', 300),
        'prices' => [
            'solo' => ['month' => env('PADDLE_PRICE_SOLO_MONTHLY'), 'year' => env('PADDLE_PRICE_SOLO_YEARLY')],
            'pro' => ['month' => env('PADDLE_PRICE_PRO_MONTHLY'), 'year' => env('PADDLE_PRICE_PRO_YEARLY')],
            'agency' => ['month' => env('PADDLE_PRICE_AGENCY_MONTHLY'), 'year' => env('PADDLE_PRICE_AGENCY_YEARLY')],
            'seat' => ['month' => env('PADDLE_PRICE_SEAT_MONTHLY'), 'year' => env('PADDLE_PRICE_SEAT_YEARLY')],
        ],
    ],

    // Failed webhooks are retried by the queue; admins are alerted after this many attempts.
    'webhook_alert_threshold' => (int) env('BILLING_WEBHOOK_ALERT_THRESHOLD', 3),
];
