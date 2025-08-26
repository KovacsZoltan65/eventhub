<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout'],
    'allowed_methods' => ['*'],

    // Pontos origin, NEM '*'
    'allowed_origins' => ['http://localhost:5173'],

    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],

    // Ha akarsz, tehetsz ide néhány fejlécet:
    'exposed_headers' => ['*'], // opcionális

    'max_age' => 0,

    // FONTOS: mert withCredentials true a kliensen
    'supports_credentials' => true,
];
