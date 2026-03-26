<?php

return [
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        static fn (string $origin): string => trim($origin),
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('APP_URL', 'http://localhost:8000')))
    ))),

    'allowed_origins_patterns' => [
        '#^https?://localhost(?::\d+)?$#',
        '#^https?://127\.0\.0\.1(?::\d+)?$#',
        '#^https?://\[::1\](?::\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'Retry-After',
        'X-Request-Id',
    ],

    'max_age' => 3600,

    'supports_credentials' => true,
];
