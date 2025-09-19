<?php
require_once '../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use App\Config\MongoDBManager;

header('Content-Type: text/plain');

try {
    echo "=== MongoDB Manager Test ===\n\n";
    
    // Test connection
    $isConnected = MongoDBManager::ping();
    echo "Connection Test: " . ($isConnected ? "✅ SUCCESS" : "❌ FAILED") . "\n";
    
    if ($isConnected) {
        // Test get collection and insert
        $collection = MongoDBManager::getCollection('test_manager');
        $result = $collection->insertOne([
            'test' => 'MongoDBManager Integration', 
            'timestamp' => new DateTime(),
            'status' => 'working',
            'php_version' => PHP_VERSION
        ]);
        
        echo "Insert Test: ✅ SUCCESS\n";
        echo "Inserted ID: " . $result->getInsertedId() . "\n";
        
        // Test find
        $document = $collection->findOne(['_id' => $result->getInsertedId()]);
        echo "Find Test: " . ($document ? "✅ SUCCESS" : "❌ FAILED") . "\n";
        
        // Test connection info
        $info = MongoDBManager::getConnectionInfo();
        echo "\n=== Connection Info ===\n";
        print_r($info);
        
        echo "\n✅ All MongoDB Manager tests passed!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
?>