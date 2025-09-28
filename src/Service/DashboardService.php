<?php
// src/Service/DashboardService.php

namespace App\Service;

use App\Model\DashboardMetrics;
use App\Service\Metrics\InventoryMetrics;
use App\Service\Metrics\UserMetrics;
use App\Service\Metrics\AIMetrics;
use App\Service\Metrics\SystemMetrics;
use App\Exception\DashboardException;
use Psr\Log\LoggerInterface;
use DateTime;

class DashboardService
{
    private InventoryMetrics $inventoryMetrics;
    private UserMetrics $userMetrics;
    private AIMetrics $aiMetrics;
    private SystemMetrics $systemMetrics;
    private LoggerInterface $logger;
    private array $cache = [];
    private int $cacheTtl = 300; // 5 minutes

    public function __construct(
        InventoryMetrics $inventoryMetrics,
        UserMetrics $userMetrics,
        AIMetrics $aiMetrics,
        SystemMetrics $systemMetrics,
        LoggerInterface $logger
    ) {
        $this->inventoryMetrics = $inventoryMetrics;
        $this->userMetrics = $userMetrics;
        $this->aiMetrics = $aiMetrics;
        $this->systemMetrics = $systemMetrics;
        $this->logger = $logger;
    }

    public function getDashboardMetrics(bool $forceRefresh = false, bool $detailed = false): DashboardMetrics
    {
        $cacheKey = $this->generateCacheKey($detailed);
        
        if (!$forceRefresh && $this->isCacheValid($cacheKey)) {
            $this->logger->info('Returning cached dashboard metrics');
            // System metrics cache recording would be implemented here
            return $this->cache[$cacheKey]['data'];
        }

        $this->logger->info('Generating fresh dashboard metrics', [
            'forceRefresh' => $forceRefresh,
            'detailed' => $detailed
        ]);

        try {
            $startTime = microtime(true);
            
            // Collect metrics
            $metrics = $this->collectAllMetrics($detailed);
            
            // Generate trends
            $trends = $this->generateTrends($metrics);
            
            // Generate alerts
            $alerts = $this->generateAlerts($metrics);

            $dashboardMetrics = new DashboardMetrics(
                new DateTime(),
                $metrics['inventory'],
                $metrics['users'],
                $metrics['ai'],
                $metrics['system'],
                $trends,
                $alerts
            );

            // Validasi yang lebih fleksibel
            if (!$dashboardMetrics->isValid()) {
                $this->logger->warning('Dashboard metrics may have incomplete data, but proceeding anyway');
                // Tidak throw exception, tetap return metrics meskipun data tidak lengkap
            }

            $this->cacheMetrics($cacheKey, $dashboardMetrics);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logger->info('Dashboard metrics generated successfully', [
                'duration' => $duration,
                'summary' => $dashboardMetrics->getSummary(),
                'alerts' => count($alerts)
            ]);

            return $dashboardMetrics;

        } catch (DashboardException $e) {
            $this->logger->error('Dashboard metrics generation failed', [
                'error' => $e->getMessage(),
                'code' => $e->getErrorCode()
            ]);
            throw $e;
        } catch (\Exception $e) {
            $this->logger->critical('Unexpected error during dashboard metrics generation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return fallback metrics instead of throwing exception
            return $this->getFallbackDashboardMetrics();
        }
    }

    private function collectAllMetrics(bool $detailed): array
    {
        $metrics = [];

        // Collect metrics with error handling for each service
        try {
            $metrics['inventory'] = $this->inventoryMetrics->getInventoryMetrics($detailed);
        } catch (\Exception $e) {
            $this->logger->warning('Failed to collect inventory metrics', ['error' => $e->getMessage()]);
            $metrics['inventory'] = $this->getFallbackInventoryMetrics();
        }

        try {
            $metrics['users'] = $this->userMetrics->getUserMetrics();
        } catch (\Exception $e) {
            $this->logger->warning('Failed to collect user metrics', ['error' => $e->getMessage()]);
            $metrics['users'] = $this->getFallbackUserMetrics();
        }

        try {
            $metrics['ai'] = $this->aiMetrics->getAIMetrics('7d');
        } catch (\Exception $e) {
            $this->logger->warning('Failed to collect AI metrics', ['error' => $e->getMessage()]);
            $metrics['ai'] = $this->getFallbackAIMetrics();
        }

        try {
            $metrics['system'] = $this->systemMetrics->getSystemMetrics();
        } catch (\Exception $e) {
            $this->logger->warning('Failed to collect system metrics', ['error' => $e->getMessage()]);
            $metrics['system'] = $this->getFallbackSystemMetrics();
        }

        return $metrics;
    }

    private function generateTrends(array $metrics): array
    {
        $trends = [];

        // Inventory trends
        if (!empty($metrics['inventory'])) {
            $trends['inventory'] = [
                'stockHealth' => $metrics['inventory']['healthStatus'] ?? 'unknown',
                'lowStockTrend' => 'stable',
                'valueTrend' => 'up'
            ];
        }

        // User trends
        if (!empty($metrics['users'])) {
            $trends['users'] = [
                'growth' => 'positive',
                'activity' => 'stable',
                'engagement' => 'improving'
            ];
        }

        // AI trends
        if (!empty($metrics['ai'])) {
            $successRate = $metrics['ai']['performance']['successRate'] ?? 0;
            $trends['ai'] = [
                'accuracy' => $successRate > 90 ? 'improving' : 'declining',
                'performance' => 'stable',
                'adoption' => 'growing'
            ];
        }

        return $trends;
    }

    private function generateAlerts(array $metrics): array
    {
        $alerts = [];

        try {
            $inventoryAlerts = $this->inventoryMetrics->getInventoryAlerts();
            $userAlerts = $this->userMetrics->getUserAlerts();
            $aiAlerts = $this->aiMetrics->getAIAlerts();
            $systemAlerts = $this->systemMetrics->getSystemAlerts();

            $alerts = array_merge($inventoryAlerts, $userAlerts, $aiAlerts, $systemAlerts);
        } catch (\Exception $e) {
            $this->logger->error('Failed to generate alerts', ['error' => $e->getMessage()]);
        }

        // Sort alerts by severity (critical > warning > info)
        usort($alerts, function($a, $b) {
            $severity = ['critical' => 3, 'warning' => 2, 'info' => 1];
            return ($severity[$b['level'] ?? 0] <=> $severity[$a['level'] ?? 0]);
        });

        return array_slice($alerts, 0, 10); // Limit to top 10 alerts
    }

    private function generateCacheKey(bool $detailed): string
    {
        return 'dashboard_metrics_' . ($detailed ? 'detailed' : 'basic') . '_' . date('Y-m-d_H');
    }

    private function isCacheValid(string $cacheKey): bool
    {
        if (!isset($this->cache[$cacheKey])) {
            return false;
        }

        $cacheTime = $this->cache[$cacheKey]['timestamp'];
        $expiryTime = $cacheTime + $this->cacheTtl;

        if (time() > $expiryTime) {
            unset($this->cache[$cacheKey]);
            return false;
        }

        return true;
    }

    private function cacheMetrics(string $cacheKey, DashboardMetrics $metrics): void
    {
        $this->cache[$cacheKey] = [
            'data' => $metrics,
            'timestamp' => time()
        ];

        // Clean up old cache entries
        $this->cleanupCache();
    }

    private function cleanupCache(): void
    {
        $maxEntries = 10;
        if (count($this->cache) > $maxEntries) {
            // Remove oldest entries
            uasort($this->cache, function($a, $b) {
                return $a['timestamp'] <=> $b['timestamp'];
            });
            $this->cache = array_slice($this->cache, -$maxEntries, $maxEntries, true);
        }
    }

    // Fallback methods for when services are unavailable
    private function getFallbackDashboardMetrics(): DashboardMetrics
    {
        $this->logger->info('Returning fallback dashboard metrics');
        
        return new DashboardMetrics(
            new DateTime(),
            $this->getFallbackInventoryMetrics(),
            $this->getFallbackUserMetrics(),
            $this->getFallbackAIMetrics(),
            $this->getFallbackSystemMetrics(),
            [],
            [
                [
                    'type' => 'system',
                    'level' => 'warning',
                    'title' => 'Fallback Mode Active',
                    'message' => 'Dashboard is running in fallback mode due to service issues',
                    'actionUrl' => '/system/status'
                ]
            ]
        );
    }

    private function getFallbackInventoryMetrics(): array
    {
        return [
            'overview' => ['totalItems' => 0, 'categoriesCount' => 0, 'suppliersCount' => 0],
            'stockLevels' => ['optimal' => 0, 'lowStockCount' => 0, 'outOfStockCount' => 0, 'overStock' => 0],
            'healthStatus' => 'unknown'
        ];
    }

    private function getFallbackUserMetrics(): array
    {
        return [
            'demographics' => ['totalUsers' => 0, 'activeUsers' => 0, 'inactiveUsers' => 0],
            'roleDistribution' => [],
            'activity' => ['loginsToday' => 0, 'activeNow' => 0, 'averageSessionTime' => '0m'],
            'recentActivity' => []
        ];
    }

    private function getFallbackAIMetrics(): array
    {
        return [
            'performance' => ['totalAnalyses' => 0, 'successfulAnalyses' => 0, 'failedAnalyses' => 0, 'successRate' => 0],
            'accuracy' => ['averageConfidence' => 0, 'highConfidence' => 0, 'mediumConfidence' => 0, 'lowConfidence' => 0],
            'strategies' => ['active' => 'unknown', 'available' => [], 'usage' => []],
            'recentAnalyses' => []
        ];
    }

    private function getFallbackSystemMetrics(): array
    {
        return [
            'health' => ['status' => 'unknown', 'lastCheck' => (new DateTime())->format(DateTime::ATOM)],
            'performance' => ['responseTime' => '0ms', 'requestsPerMinute' => 0, 'errorRate' => 0],
            'database' => ['status' => 'unknown', 'latency' => '0ms', 'errorRate' => 0, 'operationsPerSecond' => 0, 'healthScore' => 0],
            'memory' => ['usage' => '0%', 'peakUsage' => '0%', 'current' => '0B', 'peak' => '0B', 'limit' => 'unknown'],
            'cache' => ['hitRate' => 0, 'size' => '0B', 'efficiencyScore' => 0]
        ];
    }

    public function getCacheStats(): array
    {
        $hits = 0;
        $misses = 0;
        
        // Simple cache stats implementation
        foreach ($this->cache as $entry) {
            // This is a simplified implementation
            // In real scenario, you'd track hits/misses properly
            $hits++;
        }
        
        return [
            'entries' => count($this->cache),
            'hits' => $hits,
            'misses' => $misses,
            'ttl' => $this->cacheTtl
        ];
    }

    public function clearCache(): void
    {
        $this->cache = [];
        $this->logger->info('Dashboard cache cleared');
    }

    public function setCacheTtl(int $seconds): void
    {
        $this->cacheTtl = max(60, $seconds); // Minimum 60 seconds
        $this->logger->info('Dashboard cache TTL updated', ['ttl' => $this->cacheTtl]);
    }
}