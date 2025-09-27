<?php
// src/Service/Metrics/MetricsCalculator.php

namespace App\Service\Metrics;

class MetricsCalculator
{
    public static function calculateHealthStatus(int $lowStockCount, int $outOfStockCount, int $totalItems): string
    {
        if ($totalItems === 0) {
            return 'unknown';
        }

        $outOfStockRatio = ($outOfStockCount / $totalItems) * 100;
        $lowStockRatio = ($lowStockCount / $totalItems) * 100;
        
        if ($outOfStockRatio > 5) {
            return 'critical';
        }
        if ($outOfStockRatio > 2 || $lowStockRatio > 10) {
            return 'warning';
        }
        if ($lowStockRatio > 5) {
            return 'needs_attention';
        }
        return 'healthy';
    }

    public static function calculateDatabaseHealth(float $latency, float $errorRate, float $throughput): float
    {
        $latencyScore = max(0, 100 - ($latency * 10));
        $errorScore = max(0, 100 - ($errorRate * 1000));
        $throughputScore = min(100, $throughput / 10);

        return ($latencyScore * 0.4) + ($errorScore * 0.3) + ($throughputScore * 0.3);
    }

    public static function calculateCacheEfficiency(float $hitRate, float $memoryUsage, float $responseTimeImprovement): float
    {
        $hitRateScore = min(100, $hitRate * 1.5);
        $memoryScore = max(0, 100 - ($memoryUsage / 10));
        $improvementScore = min(100, $responseTimeImprovement * 20);

        return ($hitRateScore * 0.5) + ($memoryScore * 0.3) + ($improvementScore * 0.2);
    }

    public static function determineTrend(float $current, float $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? 'up' : 'stable';
        }

        $changePercentage = (($current - $previous) / $previous) * 100;
        
        if (abs($changePercentage) < 1) {
            return 'stable';
        }
        
        return $changePercentage > 0 ? 'up' : 'down';
    }

    public static function calculateChangePercentage(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return (($current - $previous) / $previous) * 100;
    }

    public static function calculateMovingAverage(array $data, int $period = 7): float
    {
        if (count($data) === 0) {
            return 0.0;
        }

        $values = array_slice($data, -$period);
        return array_sum($values) / count($values);
    }

    public static function calculateSuccessRate(int $successful, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return ($successful / $total) * 100;
    }

    public static function calculateAverageConfidence(array $analyses): float
    {
        if (empty($analyses)) {
            return 0.0;
        }

        $totalConfidence = 0;
        $count = 0;

        foreach ($analyses as $analysis) {
            if (isset($analysis['confidence_score']) && $analysis['confidence_score'] > 0) {
                $totalConfidence += $analysis['confidence_score'];
                $count++;
            }
        }

        return $count > 0 ? $totalConfidence / $count : 0.0;
    }
}