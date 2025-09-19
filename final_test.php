<?php
require 'vendor/autoload.php';

echo "=== Final Test ===\n";

// Test semua classes dan interfaces
$items = [
    'App\Config\MongoDBManager' => 'class',
    'App\Repository\UserRepository' => 'class', 
    'App\Model\User' => 'class',
    'App\Repository\IRepository' => 'interface',
    'MongoDB\Client' => 'class'
];

foreach ($items as $name => $type) {
    if ($type === 'class') {
        $exists = class_exists($name);
    } else {
        $exists = interface_exists($name);
    }
    echo $name . ': ' . ($exists ? '✅' : '❌') . "\n";
}

// Test MongoDB connection
echo "\n=== MongoDB Test ===\n";
if (class_exists('MongoDB\Client')) {
    try {
        $client = new MongoDB\Client('mongodb://localhost:27017');
        $result = $client->selectDatabase('admin')->command(['ping' => 1]);
        echo 'Direct Connection: ✅ Successful' . "\n";
    } catch (Exception $e) {
        echo 'Direct Connection: ❌ Failed - ' . $e->getMessage() . "\n";
    }
}

// Test MongoDBManager
echo "\n=== MongoDBManager Test ===\n";
if (class_exists('App\Config\MongoDBManager')) {
    try {
        App\Config\MongoDBManager::initialize();
        $connected = App\Config\MongoDBManager::ping();
        echo 'MongoDBManager Ping: ' . ($connected ? '✅ Success' : '❌ Failed') . "\n";
        
        // Test get collection
        $collection = App\Config\MongoDBManager::getCollection('test');
        echo 'Get Collection: ✅ Success' . "\n";
        
    } catch (Exception $e) {
        echo 'MongoDBManager Error: ' . $e->getMessage() . "\n";
    }
}

// Test UserRepository
echo "\n=== UserRepository Test ===\n";
if (class_exists('App\Repository\UserRepository')) {
    try {
        $userRepo = new App\Repository\UserRepository();
        echo 'UserRepository Instantiation: ✅ Success' . "\n";
        
        // Test find method
        $users = $userRepo->find();
        echo 'UserRepository Find: ✅ Success (' . count($users) . ' users)' . "\n";
        
    } catch (Exception $e) {
        echo 'UserRepository Error: ' . $e->getMessage() . "\n";
    }
}

echo "\n=== Test Complete ===\n";
?>