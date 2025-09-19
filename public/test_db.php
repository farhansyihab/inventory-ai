<?php
require_once '../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use MongoDB\Client;

header('Content-Type: text/plain');

try {
    $client = new Client($_ENV['MONGODB_URI']);
    $database = $client->selectDatabase($_ENV['MONGODB_DB']);
    $collection = $database->selectCollection('test');
    
    // Test insert
    $result = $collection->insertOne([
        'test' => 'connection', 
        'timestamp' => new DateTime(),
        'php_version' => PHP_VERSION
    ]);
    
    echo "✅ MongoDB Connection Successful!\n";
    echo "Inserted ID: " . $result->getInsertedId() . "\n";
    
    // Test read
    $document = $collection->findOne(['_id' => $result->getInsertedId()]);
    echo "Document: " . json_encode($document, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo "❌ MongoDB Connection Failed: " . $e->getMessage() . "\n";
    echo "MONGODB_URI: " . $_ENV['MONGODB_URI'] . "\n";
    echo "MONGODB_DB: " . $_ENV['MONGODB_DB'] . "\n";
}
?>