<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load test environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env.test');
$dotenv->load();

// Set default timezone
date_default_timezone_set('Asia/Jakarta');

// Test configuration
define('TEST_DB_NAME', 'inventory_ai_test');
define('TEST_MONGODB_URI', $_ENV['MONGODB_URI'] ?? 'mongodb://localhost:27017');

// Ensure test database is used
$_ENV['MONGODB_DB'] = TEST_DB_NAME;