<?php
// scripts/test-metrics-calculation.php
require_once __DIR__ . '/../vendor/autoload.php';

echo "Testing metrics calculation...\n";

// Simple test for metrics calculation
$calculator = new App\Service\Metrics\MetricsCalculator();

// Test health status calculation
$healthStatus = $calculator->calculateHealthStatus(5, 2, 100);
echo "Health Status Test: " . $healthStatus . "\n";

echo "Metrics calculation test completed.\n";
exit(0);
