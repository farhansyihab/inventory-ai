<?php
// tests/Unit/Service/Metrics/SystemMetricsTest.php

namespace Tests\Unit\Service\Metrics;

use App\Service\Metrics\SystemMetrics;
use Tests\Unit\Service\Mocks\MockPerformanceBenchmark;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SystemMetricsTest extends TestCase
{
    private SystemMetrics $systemMetrics;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        // Buat mock MongoDBManager sederhana
        $dbManager = new class {
            public function ping(): bool { return true; }
            public function getStats(): array { return ['operationsPerSecond' => 125]; }
            public function getConnectionInfo(): array { return ['status' => 'connected']; }
        };
        
        $performanceBenchmark = new MockPerformanceBenchmark();
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->systemMetrics = new SystemMetrics(
            $dbManager,
            $performanceBenchmark,
            $this->logger
        );
    }

    public function testSystemMetricsCreation(): void
    {
        $this->assertInstanceOf(SystemMetrics::class, $this->systemMetrics);
    }

    public function testGetSystemMetricsReturnsArray(): void
    {
        $metrics = $this->systemMetrics->getSystemMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('health', $metrics);
        $this->assertArrayHasKey('performance', $metrics);
    }

    public function testGetSystemAlertsReturnsArray(): void
    {
        $alerts = $this->systemMetrics->getSystemAlerts();
        
        $this->assertIsArray($alerts);
    }

    public function testSystemMetricsStructure(): void
    {
        $metrics = $this->systemMetrics->getSystemMetrics();
        
        $this->assertArrayHasKey('health', $metrics);
        $this->assertArrayHasKey('performance', $metrics);
        $this->assertArrayHasKey('database', $metrics);
    }
}