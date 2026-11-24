<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:5173')))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Authorization',
        'Content-Type',
        'Accept',
        'X-Requested-With',
        'X-Tenant-ID',
    ],

    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 3600,

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', false),
];
