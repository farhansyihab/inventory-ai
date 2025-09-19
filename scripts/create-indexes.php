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
    
    $result = MongoDBManager::createIndexes('users', $indexes);
    
    if ($result['success']) {
        echo "✅ User indexes created successfully\n";
        echo "Indexes: " . json_encode($result['indexes'], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "❌ Error creating indexes: " . $result['error'] . "\n";
        exit(1);
    }
    
    echo "✅ All indexes created successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error creating indexes: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}