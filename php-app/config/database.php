<?php
return [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_NAME'] ?? 'do4me',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASS'] ?? '',
    'charset' => 'utf8mb4',
    'port' => $_ENV['DB_PORT'] ?? 3306,
    
    // PDO options
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ],
    
    // Connection pool settings (if using connection pooling)
    'pool' => [
        'max_connections' => 20,
        'idle_timeout' => 300
    ],
    
    // Read/write connection separation (for replication)
    'read' => [
        'host' => $_ENV['DB_READ_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost'
    ],
    'write' => [
        'host' => $_ENV['DB_WRITE_HOST'] ?? $_ENV['DB_HOST'] ?? 'localhost'
    ],
    
    // Migration settings
    'migrations' => [
        'table' => 'migrations',
        'path' => __DIR__ . '/../database/migrations'
    ]
];