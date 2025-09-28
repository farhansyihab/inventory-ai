<?php
// tests/Unit/Service/Mocks/MockInventoryService.php

namespace Tests\Unit\Service\Mocks;

use App\Service\InventoryService;
use App\Repository\IInventoryRepository;
use App\Utility\Logger;

class MockInventoryService extends InventoryService
{
    public function __construct()
    {
        $inventoryRepo = new MockIInventoryRepository();
        $logger = new MockLogger();
        
        parent::__construct($inventoryRepo, $logger);
    }

    public function count(array $filter = []): int 
    { 
        return 100; 
    }
    
    public function getLowStockItems(int $threshold = 0): array 
    { 
        return ['item1', 'item2', 'item3']; 
    }
    
    public function getOutOfStockItems(): array 
    { 
        return ['item4']; 
    }
    
    public function getStats(): array 
    { 
        return [
            'categoriesCount' => 5,
            'suppliersCount' => 8,
            'overStockCount' => 2
        ]; 
    }
    
    public function getInventoryCount(): int
    {
        return 100;
    }
}