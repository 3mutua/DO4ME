<?php
return [
    'auth' => [
        'excluded_routes' => [
            '/login',
            '/register',
            '/forgot-password',
            '/reset-password',
            '/auth/callback',
            '/api/auth/login',
            '/api/auth/register',
            '/health-check'
        ],
        'session_timeout' => 7200, // 2 hours
        'rate_limiting' => [
            'login_attempts' => 5,
            'time_window' => 900 // 15 minutes
        ]
    ],
    'cors' => [
        'allowed_origins' => ['http://localhost:3000', 'https://do4me.com'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'allow_credentials' => true,
        'max_age' => 86400
    ]
];