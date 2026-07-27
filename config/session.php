<?php
declare(strict_types=1);

$env = require __DIR__ . '/env.php';

return [
    'lifetime' => (int) $env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'secure' => filter_var($env('SESSION_SECURE', false), FILTER_VALIDATE_BOOLEAN),
    'http_only' => true,
    'same_site' => 'lax',
    'encrypt' => true,
    'driver' => $env('SESSION_DRIVER', 'database'),
];
