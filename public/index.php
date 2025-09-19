<?php
require_once '../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'message' => 'Inventory AI API is running!',
    'timestamp' => time(),
    'php_version' => PHP_VERSION,
    'mongodb_extension' => extension_loaded('mongodb') ? 'loaded' : 'not loaded',
    'environment' => $_ENV['APP_ENV'] ?? 'not set'
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>