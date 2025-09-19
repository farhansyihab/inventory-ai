<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\MongoDBManager;

echo "Creating MongoDB indexes...\n";

try {
    // Create indexes for users collection
    $indexes = [
        ['key' => ['username' => 1], 'unique' => true],
        ['key' => ['email' => 1], 'unique' => true],
        ['key' => ['role' => 1]],
        ['key' => ['createdAt' => 1]]
    ];
    
    $collection = MongoDBManager::getCollection('users');
    $result = $collection->createIndexes($indexes);
    
    echo "✅ User indexes created successfully\n";
    echo "Indexes: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    echo "✅ All indexes created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error creating indexes: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}