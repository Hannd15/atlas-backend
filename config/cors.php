<?php

return [
    'allowed_origins' => array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:3000')))),

    'allowed_methods' => [
        'GET',
        'POST',
        'PUT',
        'DELETE',
        'OPTIONS',
    ],

    'allowed_headers' => [
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'Accept',
    ],
];
