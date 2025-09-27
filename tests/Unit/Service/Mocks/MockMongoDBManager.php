<?php
// tests/Unit/Service/Mocks/MockMongoDBManager.php

namespace Tests\Unit\Service\Mocks;

class MockMongoDBManager
{
    public function ping(): bool 
    { 
        return true; 
    }
    
    public function getStats(): array 
    { 
        return ['operationsPerSecond' => 125];
    }
    
    public function getConnectionInfo(): array 
    { 
        return ['status' => 'connected'];
    }
}
