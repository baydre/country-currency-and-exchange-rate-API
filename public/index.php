<?php

/**
 * Entry point for the Country & Currency API
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Router;

// Load environment variables
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Set error reporting based on environment
if (env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}

// Enable CORS for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Initialize router and handle request
try {
    $router = new Router();
    $router->dispatch();
} catch (\Exception $e) {
    // Global exception handler
    // Determine status code based on exception type
    $statusCode = 500;
    
    if ($e instanceof \App\Exceptions\ServiceUnavailableException) {
        $statusCode = 503;
    } elseif ($e instanceof \App\Exceptions\NotFoundException) {
        $statusCode = 404;
    } elseif ($e instanceof \App\Exceptions\ValidationException) {
        $statusCode = 400;
    }
    
    jsonResponse([
        'error' => $e->getMessage(),
        'details' => env('APP_DEBUG') ? $e->getTrace() : null
    ], $statusCode);
}
