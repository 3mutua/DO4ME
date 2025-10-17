<?php
return [
    'name' => 'DO4ME Platform',
    'env' => $_ENV['APP_ENV'] ?? 'production',
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'timezone' => 'UTC',
    
    'encryption' => [
        'key' => $_ENV['APP_KEY'] ?? '',
        'cipher' => 'AES-256-CBC'
    ],
    
    'session' => [
        'lifetime' => 120,
        'path' => '/',
        'domain' => $_ENV['SESSION_DOMAIN'] ?? null,
        'secure' => $_ENV['SESSION_SECURE_COOKIE'] ?? false,
        'httponly' => true,
        'same_site' => 'lax'
    ],
    
    'cors' => [
        'allowed_origins' => ['http://localhost:3000', 'https://do4me.com'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With']
    ]
];