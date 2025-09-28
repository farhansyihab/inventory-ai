<?php
// tests/Unit/Service/Mocks/MockLogger.php

namespace Tests\Unit\Service\Mocks;

use App\Utility\Logger;

class MockLogger extends Logger
{
    public function __construct()
    {
        // Panggil parent constructor dengan parameter default
        parent::__construct(null, 'INFO');
    }
    
    public function log($level, $message, array $context = []): void {}
    public function debug($message, array $context = []): void {}
    public function info($message, array $context = []): void {}
    public function error($message, array $context = []): void {}
    public function warning($message, array $context = []): void {}
}