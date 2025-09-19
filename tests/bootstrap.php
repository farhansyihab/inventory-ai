<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load test environment
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../', '.env.test');
$dotenv->load();

// Set default timezone
date_default_timezone_set('Asia/Jakarta');