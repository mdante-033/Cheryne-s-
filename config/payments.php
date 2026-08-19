<?php
declare(strict_types=1);

$env = require __DIR__ . '/env.php';

return [
    'stripe' => [
        'secret' => $env('STRIPE_SECRET', ''),
        'publishable' => $env('STRIPE_PUBLISHABLE', ''),
    ],
    'mpesa' => [
        'consumer_key' => $env('MPESA_CONSUMER_KEY', ''),
        'consumer_secret' => $env('MPESA_CONSUMER_SECRET', ''),
        'shortcode' => $env('MPESA_SHORTCODE', ''),
        'passkey' => $env('MPESA_PASSKEY', ''),
        'callback_url' => $env('MPESA_CALLBACK_URL', ''),
        'sandbox' => filter_var($env('MPESA_SANDBOX', true), FILTER_VALIDATE_BOOLEAN),
        'base_url' => $env('MPESA_BASE_URL', ''),
    ],
];
