<?php
// tests/Unit/Service/Mocks/MockPerformanceBenchmark.php

namespace Tests\Unit\Service\Mocks;

class MockPerformanceBenchmark
{
    public function getResults(): array 
    { 
        return []; 
    }
    
    public function getLatestResult(): array 
    { 
        return ['duration' => 125.5, 'success' => true];
    }
}
