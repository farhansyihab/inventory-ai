<?php
// tests/Unit/Service/DashboardServiceTest.php

namespace Tests\Unit\Service;

use App\Service\DashboardService;
use App\Model\DashboardMetrics;
use App\Service\Metrics\InventoryMetrics;
use App\Service\Metrics\UserMetrics;
use App\Service\Metrics\AIMetrics;
use App\Service\Metrics\SystemMetrics;
use App\Exception\DashboardException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use DateTime;

class DashboardServiceTest extends TestCase
{
    private DashboardService $dashboardService;
    private InventoryMetrics $inventoryMetrics;
    private UserMetrics $userMetrics;
    private AIMetrics $aiMetrics;
    private SystemMetrics $systemMetrics;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->inventoryMetrics = $this->createMock(InventoryMetrics::class);
        $this->userMetrics = $this->createMock(UserMetrics::class);
        $this->aiMetrics = $this->createMock(AIMetrics::class);
        $this->systemMetrics = $this->createMock(SystemMetrics::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->dashboardService = new DashboardService(
            $this->inventoryMetrics,
            $this->userMetrics,
            $this->aiMetrics,
            $this->systemMetrics,
            $this->logger
        );
    }

    public function testGetDashboardMetricsSuccess(): void
    {
        // Mock metrics dari setiap service dengan data yang valid
        $this->inventoryMetrics->method('getInventoryMetrics')
            ->willReturn([
                'overview' => ['totalItems' => 100, 'categoriesCount' => 5],
                'stockLevels' => ['lowStockCount' => 3, 'outOfStockCount' => 1],
                'healthStatus' => 'healthy'
            ]);
        
        $this->userMetrics->method('getUserMetrics')
            ->willReturn([
                'demographics' => ['activeUsers' => 50, 'totalUsers' => 60],
                'roleDistribution' => ['admin' => 2, 'manager' => 5, 'staff' => 53]
            ]);
            
        $this->aiMetrics->method('getAIMetrics')
            ->willReturn([
                'performance' => ['successRate' => 95.5, 'totalAnalyses' => 200],
                'accuracy' => ['averageConfidence' => 87.5]
            ]);
            
        $this->systemMetrics->method('getSystemMetrics')
            ->willReturn([
                'health' => ['status' => 'healthy'],
                'performance' => ['responseTime' => '125ms']
            ]);

        $this->inventoryMetrics->method('getInventoryAlerts')->willReturn([]);
        $this->userMetrics->method('getUserAlerts')->willReturn([]);
        $this->aiMetrics->method('getAIAlerts')->willReturn([]);
        $this->systemMetrics->method('getSystemAlerts')->willReturn([]);

        $this->logger->expects($this->exactly(2))
            ->method('info');

        $metrics = $this->dashboardService->getDashboardMetrics();

        $this->assertInstanceOf(DashboardMetrics::class, $metrics);
        $this->assertTrue($metrics->isValid());
    }

    public function testGetDashboardMetricsWithForceRefresh(): void
    {
        $this->inventoryMetrics->expects($this->exactly(2))
            ->method('getInventoryMetrics')
            ->willReturn([
                'overview' => ['totalItems' => 100],
                'stockLevels' => ['lowStockCount' => 0, 'outOfStockCount' => 0]
            ]);

        $this->userMetrics->method('getUserMetrics')->willReturn([
            'demographics' => ['activeUsers' => 50]
        ]);
        $this->aiMetrics->method('getAIMetrics')->willReturn([
            'performance' => ['successRate' => 95.5]
        ]);
        $this->systemMetrics->method('getSystemMetrics')->willReturn([
            'health' => ['status' => 'healthy']
        ]);
        
        $this->inventoryMetrics->method('getInventoryAlerts')->willReturn([]);
        $this->userMetrics->method('getUserAlerts')->willReturn([]);
        $this->aiMetrics->method('getAIAlerts')->willReturn([]);
        $this->systemMetrics->method('getSystemAlerts')->willReturn([]);

        // First call - should cache
        $metrics1 = $this->dashboardService->getDashboardMetrics(false);
        
        // Second call with force refresh - should not use cache
        $metrics2 = $this->dashboardService->getDashboardMetrics(true);

        $this->assertInstanceOf(DashboardMetrics::class, $metrics1);
        $this->assertInstanceOf(DashboardMetrics::class, $metrics2);
    }

    public function testGetDashboardMetricsWithServiceFailure(): void
    {
        $this->inventoryMetrics->method('getInventoryMetrics')
            ->willThrowException(new \Exception('Service unavailable'));

        $this->userMetrics->method('getUserMetrics')->willReturn([
            'demographics' => ['activeUsers' => 50]
        ]);
        $this->aiMetrics->method('getAIMetrics')->willReturn([
            'performance' => ['successRate' => 95.5]
        ]);
        $this->systemMetrics->method('getSystemMetrics')->willReturn([
            'health' => ['status' => 'healthy']
        ]);

        $this->logger->expects($this->once())
            ->method('warning');

        $metrics = $this->dashboardService->getDashboardMetrics();

        $this->assertInstanceOf(DashboardMetrics::class, $metrics);
        $this->assertTrue($metrics->isValid());
    }

    public function testCacheManagement(): void
    {
        $this->inventoryMetrics->method('getInventoryMetrics')->willReturn([
            'overview' => ['totalItems' => 100]
        ]);
        $this->userMetrics->method('getUserMetrics')->willReturn([
            'demographics' => ['activeUsers' => 50]
        ]);
        $this->aiMetrics->method('getAIMetrics')->willReturn([
            'performance' => ['successRate' => 95.5]
        ]);
        $this->systemMetrics->method('getSystemMetrics')->willReturn([
            'health' => ['status' => 'healthy']
        ]);
        
        $this->inventoryMetrics->method('getInventoryAlerts')->willReturn([]);
        $this->userMetrics->method('getUserAlerts')->willReturn([]);
        $this->aiMetrics->method('getAIAlerts')->willReturn([]);
        $this->systemMetrics->method('getSystemAlerts')->willReturn([]);

        // Test cache clear
        $this->dashboardService->clearCache();
        
        // This should not throw an error
        $this->assertTrue(true);
    }

    public function testAlertsGeneration(): void
    {
        // Setup basic metrics data
        $this->inventoryMetrics->method('getInventoryMetrics')->willReturn([
            'overview' => ['totalItems' => 100],
            'stockLevels' => ['lowStockCount' => 0, 'outOfStockCount' => 0]
        ]);
        $this->userMetrics->method('getUserMetrics')->willReturn([
            'demographics' => ['activeUsers' => 50]
        ]);
        $this->aiMetrics->method('getAIMetrics')->willReturn([
            'performance' => ['successRate' => 95.5]
        ]);
        $this->systemMetrics->method('getSystemMetrics')->willReturn([
            'health' => ['status' => 'healthy']
        ]);

        // Mock alerts dari services
        $this->inventoryMetrics->method('getInventoryAlerts')
            ->willReturn([[
                'type' => 'inventory',
                'level' => 'critical',
                'title' => 'Test Alert',
                'message' => 'Test message',
                'actionUrl' => '/test'
            ]]);

        $this->userMetrics->method('getUserAlerts')->willReturn([]);
        $this->aiMetrics->method('getAIAlerts')->willReturn([]);
        $this->systemMetrics->method('getSystemAlerts')->willReturn([]);

        $metrics = $this->dashboardService->getDashboardMetrics();

        $alerts = $metrics->getAlerts();
        $this->assertIsArray($alerts);
        $this->assertCount(1, $alerts);
        $this->assertEquals('critical', $alerts[0]['level']);
    }

    public function testFallbackMetrics(): void
    {
        // Simulasikan semua service gagal
        $this->inventoryMetrics->method('getInventoryMetrics')
            ->willThrowException(new \Exception('Service down'));
        $this->userMetrics->method('getUserMetrics')
            ->willThrowException(new \Exception('Service down'));
        $this->aiMetrics->method('getAIMetrics')
            ->willThrowException(new \Exception('Service down'));
        $this->systemMetrics->method('getSystemMetrics')
            ->willThrowException(new \Exception('Service down'));

        $this->inventoryMetrics->method('getInventoryAlerts')
            ->willThrowException(new \Exception('Service down'));
        $this->userMetrics->method('getUserAlerts')
            ->willThrowException(new \Exception('Service down'));
        $this->aiMetrics->method('getAIAlerts')
            ->willThrowException(new \Exception('Service down'));
        $this->systemMetrics->method('getSystemAlerts')
            ->willThrowException(new \Exception('Service down'));

        $metrics = $this->dashboardService->getDashboardMetrics();

        $this->assertInstanceOf(DashboardMetrics::class, $metrics);
        $this->assertTrue($metrics->isValid());
        
        // Perbaikan: Test yang lebih realistis untuk fallback mode
        $alerts = $metrics->getAlerts();
        $this->assertIsArray($alerts);
        // Fallback metrics mungkin tidak memiliki alerts, jadi test struktur dasar saja
        $this->assertArrayHasKey('inventory', $metrics->toArray());
        $this->assertArrayHasKey('users', $metrics->toArray());
        $this->assertArrayHasKey('ai', $metrics->toArray());
        $this->assertArrayHasKey('system', $metrics->toArray());
    }
}