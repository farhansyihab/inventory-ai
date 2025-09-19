<?php
echo "PHP Version: " . PHP_VERSION . "<br>";
echo "MongoDB Extension: " . (extension_loaded('mongodb') ? '✅ Loaded' : '❌ Not loaded') . "<br>";

// Test MongoDB connection
try {
    $client = new MongoDB\Client('mongodb://localhost:27017');
    $database = $client->selectDatabase('admin');
    $result = $database->command(['ping' => 1]);
    echo "MongoDB Connection: ✅ Successful<br>";
    echo "Ping Response: " . json_encode($result->toArray()[0]) . "<br>";
} catch (Exception $e) {
    echo "MongoDB Connection: ❌ Failed - " . $e->getMessage() . "<br>";
}

// Test MongoDBManager class
if (class_exists('App\Config\MongoDBManager')) {
    echo "MongoDBManager Class: ✅ Found<br>";
    try {
        $connected = App\Config\MongoDBManager::ping();
        echo "MongoDBManager Ping: " . ($connected ? '✅ OK' : '❌ Failed') . "<br>";
    } catch (Exception $e) {
        echo "MongoDBManager Error: " . $e->getMessage() . "<br>";
    }
} else {
    echo "MongoDBManager Class: ❌ Not found<br>";
}
?>