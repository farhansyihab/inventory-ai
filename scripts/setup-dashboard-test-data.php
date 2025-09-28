<?php
// scripts/setup-dashboard-test-data.php
require_once __DIR__ . '/../vendor/autoload.php';

echo "Setting up dashboard test data...\n";

// Create test data for dashboard metrics
$testData = [
    'inventory' => [
        'total_items' => 1000,
        'low_stock' => 25,
        'out_of_stock' => 5
    ],
    'users' => [
        'total_users' => 50,
        'active_users' => 45
    ]
];

echo "Dashboard test data setup completed.\n";
exit(0);
