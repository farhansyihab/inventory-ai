<?php
require_once '/var/www/html/inventory-ai/vendor/autoload.php';

use App\Config\MongoDBManager;
use App\Repository\UserRepository;

// Cleanup test data
MongoDBManager::initialize();
$userRepo = new UserRepository();

// Delete all test users
$result = $userRepo->deleteMany([
    'username' => [
        '$in' => [
            'testuser', 'loaduser0', 'loaduser1', 'loaduser2', 'loaduser3', 'loaduser4',
            'bulkuser0', 'bulkuser1', 'bulkuser2', 'bulkuser3', 'bulkuser4'
        ]
    ]
]);

echo "Cleaned up {$result} test users\n";

// Cleanup test collections
$collections = ['test_performance', 'test_connection', 'test_manager'];
foreach ($collections as $collection) {
    if (MongoDBManager::collectionExists($collection)) {
        MongoDBManager::getCollection($collection)->drop();
        echo "Dropped collection: $collection\n";
    }
}
