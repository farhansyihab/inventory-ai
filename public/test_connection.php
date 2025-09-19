<?php
require_once 'vendor/autoload.php';

use MongoDB\Client;

try {
    $client = new Client($_ENV['MONGODB_URI']);
    $database = $client->selectDatabase($_ENV['MONGODB_DB']);
    $collection = $database->selectCollection('test');
    
    // Test insert
    $result = $collection->insertOne(['test' => 'connection', 'timestamp' => new DateTime()]);
    echo "✅ MongoDB Connection Successful! Inserted ID: " . $result->getInsertedId();
    
} catch (Exception $e) {
    echo "❌ MongoDB Connection Failed: " . $e->getMessage();
}