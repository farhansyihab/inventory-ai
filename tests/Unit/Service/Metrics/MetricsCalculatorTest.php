<?php
// tests/Unit/Service/Metrics/MetricsCalculatorTest.php

namespace Tests\Unit\Service\Metrics;

use App\Service\Metrics\MetricsCalculator;
use PHPUnit\Framework\TestCase;

class MetricsCalculatorTest extends TestCase
{
    public function testCalculateHealthStatus(): void
    {
        $this->assertEquals('healthy', MetricsCalculator::calculateHealthStatus(2, 0, 100));
        $this->assertEquals('needs_attention', MetricsCalculator::calculateHealthStatus(6, 0, 100));
        $this->assertEquals('warning', MetricsCalculator::calculateHealthStatus(11, 2, 100));
        $this->assertEquals('critical', MetricsCalculator::calculateHealthStatus(5, 6, 100));
        $this->assertEquals('unknown', MetricsCalculator::calculateHealthStatus(0, 0, 0));
    }

    public function testCalculateDatabaseHealth(): void
    {
        $score = MetricsCalculator::calculateDatabaseHealth(10.0, 0.1, 50.0);
        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(100, $score);
        
        // Test with perfect values
        $perfectScore = MetricsCalculator::calculateDatabaseHealth(0.0, 0.0, 1000.0);
        $this->assertEquals(100, $perfectScore);
    }

    public function testCalculateCacheEfficiency(): void
    {
        $efficiency = MetricsCalculator::calculateCacheEfficiency(80.0, 50.0, 0.3);
        $this->assertGreaterThan(0, $efficiency);
        $this->assertLessThanOrEqual(100, $efficiency);
    }

    public function testDetermineTrend(): void
    {
        $this->assertEquals('up', MetricsCalculator::determineTrend(110, 100));
        $this->assertEquals('down', MetricsCalculator::determineTrend(90, 100));
        $this->assertEquals('stable', MetricsCalculator::determineTrend(100.5, 100));
        $this->assertEquals('up', MetricsCalculator::determineTrend(10, 0));
        $this->assertEquals('stable', MetricsCalculator::determineTrend(0, 0));
    }

    public function testCalculateChangePercentage(): void
    {
        $this->assertEquals(10.0, MetricsCalculator::calculateChangePercentage(110, 100));
        $this->assertEquals(-10.0, MetricsCalculator::calculateChangePercentage(90, 100));
        $this->assertEquals(100.0, MetricsCalculator::calculateChangePercentage(10, 0));
        $this->assertEquals(0.0, MetricsCalculator::calculateChangePercentage(0, 0));
    }

    public function testCalculateMovingAverage(): void
    {
        $data = [10, 20, 30, 40, 50];
        $average = MetricsCalculator::calculateMovingAverage($data, 5);
        $this->assertEquals(30.0, $average);
        
        $emptyAverage = MetricsCalculator::calculateMovingAverage([], 5);
        $this->assertEquals(0.0, $emptyAverage);
    }

    public function testCalculateSuccessRate(): void
    {
        $this->assertEquals(75.0, MetricsCalculator::calculateSuccessRate(3, 4));
        $this->assertEquals(100.0, MetricsCalculator::calculateSuccessRate(5, 5));
        $this->assertEquals(0.0, MetricsCalculator::calculateSuccessRate(0, 5));
        $this->assertEquals(0.0, MetricsCalculator::calculateSuccessRate(0, 0));
    }

    public function testCalculateAverageConfidence(): void
    {
        $analyses = [
            ['confidence_score' => 80.0],
            ['confidence_score' => 90.0],
            ['confidence_score' => 70.0],
            ['confidence_score' => 0.0], // Should be ignored
            ['no_confidence' => 50.0] // Should be ignored
        ];
        
        $average = MetricsCalculator::calculateAverageConfidence($analyses);
        $this->assertEquals(80.0, $average);
        
        $emptyAverage = MetricsCalculator::calculateAverageConfidence([]);
        $this->assertEquals(0.0, $emptyAverage);
    }
}