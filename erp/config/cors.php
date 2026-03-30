<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'events/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:5173'),
        'http://localhost:5173',
        'http://localhost:3000',
        'http://localhost:4175',
        'http://127.0.0.1:4175',
        'http://172.19.254.160:4175',
        'http://172.19.254.160:4176',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['Idempotency-Key', 'X-Event-Stream'],
    'max_age' => 0,
    'supports_credentials' => true,
];
