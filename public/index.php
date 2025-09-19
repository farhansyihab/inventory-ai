<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables early
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad(); // safeLoad to avoid exception in CI if no .env

// Initialize error handling (display errors only in development)
$errorHandler = new App\Middleware\ErrorHandler(null, ($_ENV['APP_ENV'] ?? 'production') === 'development');
$errorHandler->register();

// Initialize Logger and Mongo
$logger = new App\Utility\Logger();
App\Config\MongoDBManager::initialize($logger);

// Initialize Router
$router = new App\Utility\Router();

// Define routes (same as before)
$router->get('/', function () {
    return [
        'status' => 'success',
        'message' => 'Inventory AI API is running!',
        'timestamp' => time(),
        'php_version' => PHP_VERSION,
        'environment' => $_ENV['APP_ENV'] ?? 'not set',
        'mongodb_connected' => App\Config\MongoDBManager::ping()
    ];
});

$router->get('/health', function () {
    return [
        'status' => 'healthy',
        'timestamp' => time(),
        'services' => [
            'mongodb' => App\Config\MongoDBManager::ping() ? 'connected' : 'disconnected',
            'php' => 'running'
        ]
    ];
});

// API group...
$router->group('/api', function ($router) {
    $router->post('/auth/register', 'App\Controller\AuthController@register');
    $router->post('/auth/login', 'App\Controller\AuthController@login');

    $router->get('/users', 'App\Controller\UserController@listUsers');
    $router->get('/users/{id}', 'App\Controller\UserController@getUser');
    $router->post('/users', 'App\Controller\UserController@createUser');

    $router->get('/inventory', 'App\Controller\InventoryController@listItems');
});

// 404 handler
$router->setNotFoundHandler(function () {
    http_response_code(404);
    return [
        'status' => 'error',
        'message' => 'Endpoint not found',
        'timestamp' => time(),
        'documentation' => '/api/docs'
    ];
});

// Dispatch
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

// normalize path and remove query
$parsed = parse_url($uri);
$path = $parsed['path'] ?? '/';

// Base path support (robust): strip base if present with exact prefix
$basePath = '/inventory-ai';
if ($basePath !== '' && str_starts_with($path, $basePath)) {
    $path = '/' . ltrim(substr($path, strlen($basePath)), '/');
}

// Security headers & CORS (adjust for prod allowed origins)
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: no-referrer-when-downgrade');
header('Access-Control-Allow-Origin: *'); // change in prod
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// short-circuit OPTIONS
if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// dispatch and handle result
$response = $router->dispatch($method, $path);

// If route returned array that contains 'statusCode', use it
if (is_array($response) && isset($response['statusCode']) && is_int($response['statusCode'])) {
    http_response_code($response['statusCode']);
    unset($response['statusCode']);
}

// final output
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
