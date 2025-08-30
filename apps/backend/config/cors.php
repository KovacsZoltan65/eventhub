<?php

return [
    'paths' => [
        'api/*',
        'organizer/*',        // ⬅️ EZ HIÁNYZOTT
        'admin/*',            // (ha van)
        'sanctum/csrf-cookie',
        'login', 'logout', 'user', 'user/*',
    ],
    'allowed_methods' => ['*'],

    'allowed_origins_patterns' => [],
    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ],
    'allowed_headers' => ['*'],

    // Ha akarsz, tehetsz ide néhány fejlécet:
    'exposed_headers' => ['*'], // opcionális

    'max_age' => 0,

    // FONTOS: mert withCredentials true a kliensen
    'supports_credentials' => true,
];
