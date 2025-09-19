<?php
require_once 'vendor/autoload.php';

use App\Config\MongoDBManager;

echo "=== Testing Enhanced MongoDBManager ===\n";

MongoDBManager::initialize();

// Test basic connectivity
echo 'Ping: ' . (MongoDBManager::ping() ? '✅' : '❌') . PHP_EOL;

// Test collection existence
echo 'Collection exists (users): ' . (MongoDBManager::collectionExists('users') ? '✅' : '❌') . PHP_EOL;

// Test stats
$stats = MongoDBManager::getStats();
echo 'DB Stats: ' . ($stats['success'] ? '✅' : '❌') . PHP_EOL;

// Test server version
$version = MongoDBManager::getServerVersion();
echo 'Server Version: ' . ($version['success'] ? '✅' : '❌') . PHP_EOL;

// Test connection info
$info = MongoDBManager::getConnectionInfo();
echo 'Connection Info: ✅' . PHP_EOL;
print_r($info);

echo "=== Test Complete ===\n";
?>