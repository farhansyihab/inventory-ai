<?php
// tests/Unit/Model/DashboardMetricsTest.php

namespace Tests\Unit\Model;

use App\Model\DashboardMetrics;
use DateTime;
use PHPUnit\Framework\TestCase;

class DashboardMetricsTest extends TestCase
{
    private DashboardMetrics $metrics;
    private DateTime $generatedAt;

    protected function setUp(): void
    {
        $this->generatedAt = new DateTime();
        $this->metrics = new DashboardMetrics(
            $this->generatedAt,
            ['overview' => ['totalItems' => 100]], // Data yang lebih realistis
            ['demographics' => ['activeUsers' => 50]],
            ['performance' => ['successRate' => 95.5]],
            ['health' => ['status' => 'healthy']]
        );
    }

    public function testInitialization(): void
    {
        $this->assertEquals($this->generatedAt, $this->metrics->getGeneratedAt());
        $this->assertEquals(['overview' => ['totalItems' => 100]], $this->metrics->getInventory());
        $this->assertEquals(['demographics' => ['activeUsers' => 50]], $this->metrics->getUsers());
        $this->assertEquals(['performance' => ['successRate' => 95.5]], $this->metrics->getAi());
        $this->assertEquals(['health' => ['status' => 'healthy']], $this->metrics->getSystem());
        $this->assertEmpty($this->metrics->getTrends());
        $this->assertEmpty($this->metrics->getAlerts());
    }

    public function testToArray(): void
    {
        $array = $this->metrics->toArray();

        $this->assertEquals($this->generatedAt->format(\DateTimeInterface::ATOM), $array['generatedAt']);
        $this->assertEquals(['overview' => ['totalItems' => 100]], $array['inventory']);
        $this->assertEquals(['demographics' => ['activeUsers' => 50]], $array['users']);
        $this->assertEquals(['performance' => ['successRate' => 95.5]], $array['ai']);
        $this->assertEquals(['health' => ['status' => 'healthy']], $array['system']);
        $this->assertArrayHasKey('trends', $array);
        $this->assertArrayHasKey('alerts', $array);
    }

    public function testJsonSerialize(): void
    {
        $json = $this->metrics->jsonSerialize();
        $this->assertEquals($this->metrics->toArray(), $json);
    }

    public function testIsValid(): void
    {
        $this->assertTrue($this->metrics->isValid());

        // Test dengan data kosong tetapi struktur ada
        $emptyMetrics = new DashboardMetrics(new DateTime(), [], [], [], []);
        $this->assertTrue($emptyMetrics->isValid()); // Should be valid karena memiliki struktur

        // Test dengan alerts saja
        $alertMetrics = new DashboardMetrics(new DateTime(), [], [], [], [], [], [['level' => 'warning']]);
        $this->assertTrue($alertMetrics->isValid());
    }

    public function testGetSummary(): void
    {
        $summary = $this->metrics->getSummary();
        
        $this->assertStringContainsString('Inventory: 100 items', $summary);
        $this->assertStringContainsString('Users: 50 active', $summary);
        $this->assertStringContainsString('AI: 95.5% success rate', $summary);
    }

    public function testSetters(): void
    {
        $newInventory = ['overview' => ['totalItems' => 200]];
        $newUsers = ['demographics' => ['activeUsers' => 75]];
        $newAi = ['performance' => ['successRate' => 98.0]];
        $newSystem = ['health' => ['status' => 'degraded']];
        $newTrends = ['inventory' => ['trend' => 'up']];
        $newAlerts = [['type' => 'warning', 'message' => 'Test alert']];

        $this->metrics
            ->setInventory($newInventory)
            ->setUsers($newUsers)
            ->setAi($newAi)
            ->setSystem($newSystem)
            ->setTrends($newTrends)
            ->setAlerts($newAlerts);

        $this->assertEquals($newInventory, $this->metrics->getInventory());
        $this->assertEquals($newUsers, $this->metrics->getUsers());
        $this->assertEquals($newAi, $this->metrics->getAi());
        $this->assertEquals($newSystem, $this->metrics->getSystem());
        $this->assertEquals($newTrends, $this->metrics->getTrends());
        $this->assertEquals($newAlerts, $this->metrics->getAlerts());
    }

    public function testEmptySummary(): void
    {
        $emptyMetrics = new DashboardMetrics(new DateTime());
        $summary = $emptyMetrics->getSummary();
        
        $this->assertEquals('', $summary);
    }

    public function testHasCriticalAlerts(): void
    {
        $this->assertFalse($this->metrics->hasCriticalAlerts());

        $metricsWithAlerts = new DashboardMetrics(
            new DateTime(),
            [], [], [], [], [],
            [['level' => 'info'], ['level' => 'critical']]
        );
        
        $this->assertTrue($metricsWithAlerts->hasCriticalAlerts());
    }

    public function testGetAlertCount(): void
    {
        $metricsWithAlerts = new DashboardMetrics(
            new DateTime(),
            [], [], [], [], [],
            [
                ['level' => 'critical'],
                ['level' => 'warning'],
                ['level' => 'warning'],
                ['level' => 'info']
            ]
        );
        
        $counts = $metricsWithAlerts->getAlertCount();
        $this->assertEquals(1, $counts['critical']);
        $this->assertEquals(2, $counts['warning']);
        $this->assertEquals(1, $counts['info']);
    }
}