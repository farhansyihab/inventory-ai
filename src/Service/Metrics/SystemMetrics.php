<?php
// src/Service/Metrics/SystemMetrics.php

namespace App\Service\Metrics;

use App\Manager\MongoDBManager;
use App\Service\PerformanceBenchmark;
use App\Exception\DashboardException;
use Psr\Log\LoggerInterface;

class SystemMetrics
{
    private MongoDBManager $dbManager;
    private PerformanceBenchmark $performanceBenchmark;
    private LoggerInterface $logger;
    private array $cache = [];
    private int $cacheHits = 0;
    private int $cacheMisses = 0;

    public function __construct(
        MongoDBManager $dbManager,
        PerformanceBenchmark $performanceBenchmark,
        LoggerInterface $logger
    ) {
        $this->dbManager = $dbManager;
        $this->performanceBenchmark = $performanceBenchmark;
        $this->logger = $logger;
    }

    public function getSystemMetrics(): array
    {
        try {
            $this->logger->info('Collecting system metrics');

            $healthStatus = $this->checkSystemHealth();
            $performanceMetrics = $this->getPerformanceMetrics();
            $databaseMetrics = $this->getDatabaseMetrics();
            $memoryMetrics = $this->getMemoryMetrics();
            $cacheMetrics = $this->getCacheMetrics();

            $metrics = [
                'health' => $healthStatus,
                'performance' => $performanceMetrics,
                'database' => $databaseMetrics,
                'memory' => $memoryMetrics,
                'cache' => $cacheMetrics
            ];

            $this->logger->info('System metrics collected successfully', [
                'status' => $healthStatus['status'],
                'responseTime' => $performanceMetrics['responseTime']
            ]);

            return $metrics;

        } catch (\Exception $e) {
            $this->logger->error('Failed to collect system metrics', [
                'error' => $e->getMessage()
            ]);

            throw DashboardException::serviceUnavailable('System', $e);
        }
    }

    private function checkSystemHealth(): array
    {
        $dbConnected = $this->dbManager->ping();
        $responseTime = $this->getAverageResponseTime();

        $status = $dbConnected && $responseTime < 2000 ? 'healthy' : 'degraded';
        if (!$dbConnected || $responseTime > 5000) {
            $status = 'unhealthy';
        }

        return [
            'status' => $status,
            'lastCheck' => (new \DateTime())->format(\DateTime::ATOM)
        ];
    }

    private function getPerformanceMetrics(): array
    {
        $benchmarkResults = $this->performanceBenchmark->getResults();
        $latestResult = $this->performanceBenchmark->getLatestResult();

        $responseTime = $latestResult['duration'] ?? 0;
        $requestsPerMinute = $this->calculateRequestsPerMinute($benchmarkResults);
        $errorRate = $this->calculateErrorRate($benchmarkResults);

        return [
            'responseTime' => round($responseTime, 2) . 'ms',
            'requestsPerMinute' => $requestsPerMinute,
            'errorRate' => round($errorRate, 2)
        ];
    }

    private function getDatabaseMetrics(): array
    {
        $pingResult = $this->dbManager->ping();
        $connectionInfo = $this->dbManager->getConnectionInfo();
        $stats = $this->dbManager->getStats();

        $latency = $pingResult ? 0.1 : 999.9; // Simplified latency calculation
        $errorRate = 0.0; // This would come from database error logging
        $throughput = $stats['operationsPerSecond'] ?? 0;

        return [
            'status' => $pingResult ? 'connected' : 'disconnected',
            'latency' => round($latency, 2) . 'ms',
            'errorRate' => $errorRate,
            'operationsPerSecond' => $throughput,
            'healthScore' => round(MetricsCalculator::calculateDatabaseHealth($latency, $errorRate, $throughput), 1)
        ];
    }

    private function getMemoryMetrics(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryPeak = memory_get_peak_usage(true);
        $memoryLimit = ini_get('memory_limit');

        $usagePercent = ($memoryUsage / $this->convertToBytes($memoryLimit)) * 100;
        $peakPercent = ($memoryPeak / $this->convertToBytes($memoryLimit)) * 100;

        return [
            'usage' => round($usagePercent, 1) . '%',
            'peakUsage' => round($peakPercent, 1) . '%',
            'current' => $this->formatBytes($memoryUsage),
            'peak' => $this->formatBytes($memoryPeak),
            'limit' => $memoryLimit
        ];
    }

    private function getCacheMetrics(): array
    {
        $total = $this->cacheHits + $this->cacheMisses;
        $hitRate = $total > 0 ? ($this->cacheHits / $total) * 100 : 0;

        return [
            'hitRate' => round($hitRate, 1),
            'size' => $this->formatBytes(memory_get_usage(true) - memory_get_usage(false)),
            'efficiencyScore' => round(MetricsCalculator::calculateCacheEfficiency($hitRate, 0, 0), 1)
        ];
    }

    private function calculateRequestsPerMinute(array $benchmarkResults): int
    {
        if (empty($benchmarkResults)) {
            return 0;
        }

        $lastMinute = array_filter($benchmarkResults, function($result) {
            return time() - strtotime($result['timestamp']) < 60;
        });

        return count($lastMinute);
    }

    private function calculateErrorRate(array $benchmarkResults): float
    {
        if (empty($benchmarkResults)) {
            return 0.0;
        }

        $errorCount = count(array_filter($benchmarkResults, fn($result) => $result['success'] === false));
        return ($errorCount / count($benchmarkResults)) * 100;
    }

    private function getAverageResponseTime(): float
    {
        $results = $this->performanceBenchmark->getResults();
        if (empty($results)) {
            return 0.0;
        }

        $durations = array_column($results, 'duration');
        return array_sum($durations) / count($durations);
    }

    private function convertToBytes(string $size): int
    {
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $size);
        $size = preg_replace('/[^0-9\.]/', '', $size);
        
        if ($unit) {
            return (int)round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }
        
        return (int)round($size);
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public function recordCacheHit(): void
    {
        $this->cacheHits++;
    }

    public function recordCacheMiss(): void
    {
        $this->cacheMisses++;
    }

    public function getSystemAlerts(): array
    {
        $metrics = $this->getSystemMetrics();
        $alerts = [];

        if ($metrics['health']['status'] === 'unhealthy') {
            $alerts[] = [
                'type' => 'system',
                'level' => 'critical',
                'title' => 'System Unhealthy',
                'message' => 'System health check failed',
                'actionUrl' => '/system/status'
            ];
        }

        $responseTime = (float)str_replace('ms', '', $metrics['performance']['responseTime']);
        if ($responseTime > 2000) {
            $alerts[] = [
                'type' => 'system',
                'level' => 'critical',
                'title' => 'High Response Time',
                'message' => sprintf('Average response time is %.0fms', $responseTime),
                'actionUrl' => '/system/performance'
            ];
        } elseif ($responseTime > 500) {
            $alerts[] = [
                'type' => 'system',
                'level' => 'warning',
                'title' => 'Degraded Performance',
                'message' => sprintf('Response time is elevated at %.0fms', $responseTime),
                'actionUrl' => '/system/performance'
            ];
        }

        $memoryUsage = (float)str_replace('%', '', $metrics['memory']['usage']);
        if ($memoryUsage > 90) {
            $alerts[] = [
                'type' => 'system',
                'level' => 'critical',
                'title' => 'High Memory Usage',
                'message' => sprintf('Memory usage at %.1f%%', $memoryUsage),
                'actionUrl' => '/system/memory'
            ];
        } elseif ($memoryUsage > 80) {
            $alerts[] = [
                'type' => 'system',
                'level' => 'warning',
                'title' => 'Elevated Memory Usage',
                'message' => sprintf('Memory usage at %.1f%%', $memoryUsage),
                'actionUrl' => '/system/memory'
            ];
        }

        if (!$this->dbManager->ping()) {
            $alerts[] = [
                'type' => 'system',
                'level' => 'critical',
                'title' => 'Database Connection Lost',
                'message' => 'Cannot connect to MongoDB',
                'actionUrl' => '/system/database'
            ];
        }

        return $alerts;
    }
}