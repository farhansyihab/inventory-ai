<?php
require_once '../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Initialize error handling
$errorHandler = new App\Middleware\ErrorHandler(null, $_ENV['APP_ENV'] === 'development');
$errorHandler->register();

// Initialize MongoDB Manager with logger
$logger = new App\Utility\Logger();
App\Config\MongoDBManager::initialize($logger);

// Initialize Router
$router = new App\Utility\Router();

// Define routes
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

// API Routes group
$router->group('/api', function ($router) {
    // Authentication routes (to be implemented)
    $router->post('/auth/register', 'App\Controller\AuthController@register');
    $router->post('/auth/login', 'App\Controller\AuthController@login');
    
    // User routes (to be implemented)
    $router->get('/users', 'App\Controller\UserController@listUsers');
    $router->get('/users/{id}', 'App\Controller\UserController@getUser');
    $router->post('/users', 'App\Controller\UserController@createUser');
    
    // Inventory routes (to be implemented)
    $router->get('/inventory', 'App\Controller\InventoryController@listItems');
});

// Set 404 handler
$router->setNotFoundHandler(function () {
    http_response_code(404);
    return [
        'status' => 'error',
        'message' => 'Endpoint not found',
        'timestamp' => time(),
        'documentation' => '/api/docs' // TODO: Add API documentation
    ];
});

// Dispatch the request
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

// Remove base path if using subdirectory
$basePath = '/inventory-ai';
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

$response = $router->dispatch($method, $path);

// Send response
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);