<?php
/**
 * DO4ME Platform - Front Controller
 * Main entry point for all HTTP requests
 */

// Enable error reporting for development
if ($_ENV['APP_ENV'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Define application constants
define('APP_ROOT', dirname(__DIR__));
define('PUBLIC_ROOT', __DIR__);
define('UPLOAD_ROOT', PUBLIC_ROOT . '/assets/uploads');
define('CACHE_ROOT', APP_ROOT . '/storage/cache');

// Ensure upload and cache directories exist
if (!is_dir(UPLOAD_ROOT)) {
    mkdir(UPLOAD_ROOT, 0755, true);
}
if (!is_dir(CACHE_ROOT)) {
    mkdir(CACHE_ROOT, 0755, true);
}

// Set default timezone
date_default_timezone_set('UTC');

// Load Composer autoloader
require_once APP_ROOT . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(APP_ROOT);
$dotenv->safeLoad();

// Initialize session
session_start();

// Register error handlers
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) {
        return false;
    }
    
    $error = [
        'type' => $errno,
        'message' => $errstr,
        'file' => $errfile,
        'line' => $errline,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    error_log("PHP Error: " . json_encode($error));
    
    if ($_ENV['APP_ENV'] === 'development') {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Internal Server Error', 'details' => $error]);
        exit;
    }
    
    return true;
});

set_exception_handler(function($exception) {
    $error = [
        'message' => $exception->getMessage(),
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    error_log("Uncaught Exception: " . json_encode($error));
    
    if ($_ENV['APP_ENV'] === 'development') {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Internal Server Error', 'details' => $error]);
    } else {
        http_response_code(500);
        include APP_ROOT . '/app/views/errors/500.php';
    }
    
    exit;
});

// Handle CORS preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: ' . ($_ENV['ALLOWED_ORIGINS'] ?? '*'));
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    header('Access-Control-Max-Age: 86400');
    exit(0);
}

// Set CORS headers for API requests
if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
    header('Access-Control-Allow-Origin: ' . ($_ENV['ALLOWED_ORIGINS'] ?? '*'));
    header('Access-Control-Allow-Credentials: true');
}

// Initialize application
try {
    // Load configuration
    $config = require APP_ROOT . '/config/app.php';
    
    // Initialize database connection
    $database = App\Core\Database::getInstance();
    
    // Initialize session manager
    $session = new App\Core\Session();
    
    // Load routes
    $router = require APP_ROOT . '/config/routes.php';
    
    // Handle the request
    $router->dispatch();
    
} catch (Exception $e) {
    // Log the error
    error_log("Application Error: " . $e->getMessage());
    
    // Return appropriate error response
    if (strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
        // API error response
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $_ENV['APP_ENV'] === 'development' ? $e->getMessage() : 'Internal Server Error'
        ]);
    } else {
        // Web error response
        http_response_code(500);
        include APP_ROOT . '/app/views/errors/500.php';
    }
    
    exit;
}