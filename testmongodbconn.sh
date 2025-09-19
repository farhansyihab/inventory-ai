# Test manual MongoDB connection
php -r "
if (extension_loaded('mongodb')) {
    echo 'MongoDB extension: ✅ Loaded' . \"\n\";
    
    try {
        \$client = new MongoDB\Client('mongodb://localhost:27017');
        \$result = \$client->selectDatabase('admin')->command(['ping' => 1]);
        echo 'MongoDB connection: ✅ Successful' . \"\n\";
        echo 'Ping response: ' . json_encode(\$result->toArray()[0]) . \"\n\";
    } catch (Exception \$e) {
        echo 'MongoDB connection: ❌ Failed - ' . \$e->getMessage() . \"\n\";
    }
} else {
    echo 'MongoDB extension: ❌ Not loaded' . \"\n\";
}
"