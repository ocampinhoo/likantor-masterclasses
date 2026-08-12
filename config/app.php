<?php

declare(strict_types=1);

return [
    'name' => 'Likantor Masterclasses',
    'env' => env('APP_ENV', 'production'),
    'debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),
    'url' => rtrim(env('APP_URL', ''), '/'),
    'key' => env('APP_KEY', ''),
    'timezone' => env('APP_TIMEZONE', 'America/Mexico_City'),
    'session_lifetime' => (int) env('SESSION_LIFETIME', 7200),
];
