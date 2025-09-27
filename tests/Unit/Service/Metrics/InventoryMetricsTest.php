<?php
// tests/Unit/Service/Metrics/InventoryMetricsTest.php

namespace Tests\Unit\Service\Metrics;

use App\Service\Metrics\InventoryMetrics;
use Tests\Unit\Service\Mocks\MockInventoryService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class InventoryMetricsTest extends TestCase
{
    private InventoryMetrics $inventoryMetrics;
    private MockInventoryService $inventoryService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->inventoryService = new MockInventoryService();
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->inventoryMetrics = new InventoryMetrics($this->inventoryService, $this->logger);
    }

    public function testInventoryMetricsCreation(): void
    {
        $this->assertInstanceOf(InventoryMetrics::class, $this->inventoryMetrics);
    }

    public function testGetInventoryMetricsReturnsArray(): void
    {
        $metrics = $this->inventoryMetrics->getInventoryMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('overview', $metrics);
        $this->assertArrayHasKey('stockLevels', $metrics);
        $this->assertArrayHasKey('healthStatus', $metrics);
    }

    public function testGetInventoryAlertsReturnsArray(): void
    {
        $alerts = $this->inventoryMetrics->getInventoryAlerts();
        
        $this->assertIsArray($alerts);
    }

    public function testInventoryMetricsStructure(): void
    {
        $metrics = $this->inventoryMetrics->getInventoryMetrics();
        
        // Test overview structure
        $this->assertArrayHasKey('totalItems', $metrics['overview']);
        $this->assertArrayHasKey('categoriesCount', $metrics['overview']);
        $this->assertArrayHasKey('suppliersCount', $metrics['overview']);
        
        // Test stockLevels structure
        $this->assertArrayHasKey('optimal', $metrics['stockLevels']);
        $this->assertArrayHasKey('lowStockCount', $metrics['stockLevels']);
        $this->assertArrayHasKey('outOfStockCount', $metrics['stockLevels']);
        $this->assertArrayHasKey('overStock', $metrics['stockLevels']);
        
        // Test healthStatus
        $this->assertIsString($metrics['healthStatus']);
    }

    public function testInventoryMetricsWithDetailedTrue(): void
    {
        $metrics = $this->inventoryMetrics->getInventoryMetrics(true);
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('valueAnalysis', $metrics);
        $this->assertArrayHasKey('movement', $metrics);
    }

    public function testInventoryMetricsValues(): void
    {
        $metrics = $this->inventoryMetrics->getInventoryMetrics();
        
        $this->assertEquals(100, $metrics['overview']['totalItems']);
        $this->assertEquals(5, $metrics['overview']['categoriesCount']);
        $this->assertEquals(3, $metrics['stockLevels']['lowStockCount']);
        $this->assertEquals(1, $metrics['stockLevels']['outOfStockCount']);
    }
}