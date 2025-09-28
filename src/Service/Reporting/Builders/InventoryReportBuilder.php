<?php
declare(strict_types=1);

namespace App\Service\Reporting\Builders;

use App\Model\Report\ReportDefinition;
use App\Model\Report\ReportResult;
use App\Service\IInventoryService;
use App\Service\AIService;
use App\Service\Reporting\IReportBuilder;
use Psr\Log\LoggerInterface;
use App\Utility\Logger;

/**
 * InventoryReportBuilder - Builder khusus untuk laporan inventory
 * Mengikuti pola yang konsisten dengan service lainnya
 */
class InventoryReportBuilder implements IReportBuilder
{
    private IInventoryService $inventoryService;
    private AIService $aiService;
    private LoggerInterface $logger;

    public function __construct(
        IInventoryService $inventoryService,
        AIService $aiService,
        LoggerInterface $logger = null
    ) {
        $this->inventoryService = $inventoryService;
        $this->aiService = $aiService;
        $this->logger = $logger ?? Logger::getLogger();
    }

    public function buildReport(ReportDefinition $definition): ReportResult
    {
        $startTime = microtime(true);
        
        $this->logger->info('Building inventory report', [
            'reportId' => $definition->getId(),
            'filters' => $definition->getFilters(),
            'dateRange' => $definition->getDateRange()?->toArray()
        ]);

        try {
            // Prepare filters untuk inventory service
            $filters = $this->buildInventoryFilters($definition);
            
            // Get data dari inventory service
            $inventoryData = $this->inventoryService->listItems($filters, [
                'sort' => $definition->getSorting(),
                'limit' => $definition->getMetadata()['max_records'] ?? 1000
            ]);

            // Generate summary statistics
            $summary = $this->generateSummary($inventoryData);
            
            // Generate insights menggunakan AI (jika available)
            $insights = $this->generateAIInsights($inventoryData);
            
            // Generate recommendations
            $recommendations = $this->generateRecommendations($summary, $insights);

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $result = ReportResult::createSuccess(
                $definition,
                $summary,
                $inventoryData,
                $insights,
                $recommendations,
                $executionTime
            );

            $this->logger->info('Inventory report built successfully', [
                'reportId' => $definition->getId(),
                'recordCount' => count($inventoryData),
                'executionTime' => $executionTime
            ]);

            return $result;

        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger->error('Inventory report build failed', [
                'reportId' => $definition->getId(),
                'error' => $e->getMessage(),
                'executionTime' => $executionTime
            ]);

            return ReportResult::createError($definition, $e->getMessage(), $executionTime);
        }
    }

    public function buildComparativeReport(ReportDefinition $definition, array $comparisonData): ReportResult
    {
        $this->logger->info('Building comparative inventory report', [
            'reportId' => $definition->getId(),
            'comparisonDataPoints' => count($comparisonData)
        ]);

        // Stub implementation untuk comparative reports
        // Akan diimplementasi lengkap di phase berikutnya
        $baseResult = $this->buildReport($definition);
        
        // Add comparative analysis
        $comparativeInsights = $this->analyzeComparativeData($baseResult->getSummary(), $comparisonData);
        
        $baseResult->setInsights(array_merge(
            $baseResult->getInsights(),
            $comparativeInsights
        ));

        return $baseResult;
    }

    public function buildPredictiveReport(ReportDefinition $definition, int $forecastDays): ReportResult
    {
        $this->logger->info('Building predictive inventory report', [
            'reportId' => $definition->getId(),
            'forecastDays' => $forecastDays
        ]);

        try {
            // Get current inventory data
            $currentData = $this->inventoryService->listItems([], ['limit' => 500]);
            
            // Use AI service untuk prediction
            $predictions = $this->aiService->predictStockNeeds($currentData, $forecastDays);
            
            $summary = $this->generatePredictiveSummary($currentData, $predictions, $forecastDays);
            $insights = $this->generatePredictiveInsights($predictions);
            $recommendations = $this->generatePredictiveRecommendations($predictions);

            $result = ReportResult::createSuccess(
                $definition,
                $summary,
                $predictions,
                $insights,
                $recommendations,
                0.0 // Execution time akan dihitung nanti
            );

            $this->logger->info('Predictive inventory report built successfully', [
                'reportId' => $definition->getId(),
                'predictionsCount' => count($predictions)
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Predictive inventory report build failed', [
                'reportId' => $definition->getId(),
                'error' => $e->getMessage()
            ]);

            return ReportResult::createError($definition, $e->getMessage());
        }
    }

    public function buildRealTimeReport(ReportDefinition $definition): ReportResult
    {
        $this->logger->info('Building real-time inventory report', [
            'reportId' => $definition->getId()
        ]);

        try {
            // Focus on current stock levels and alerts
            $lowStockItems = $this->inventoryService->getLowStockItems(10); // Threshold 10
            $outOfStockItems = $this->inventoryService->getOutOfStockItems();
            $recentlyUpdated = $this->inventoryService->listItems([], [
                'sort' => ['updatedAt' => -1],
                'limit' => 50
            ]);

            $summary = [
                'recordCount' => count($lowStockItems) + count($outOfStockItems),
                'lowStockCount' => count($lowStockItems),
                'outOfStockCount' => count($outOfStockItems),
                'lastUpdated' => date('Y-m-d H:i:s'),
                'alertLevel' => $this->calculateAlertLevel($lowStockItems, $outOfStockItems)
            ];

            $details = [
                'lowStockItems' => $lowStockItems,
                'outOfStockItems' => $outOfStockItems,
                'recentlyUpdated' => $recentlyUpdated
            ];

            $insights = $this->generateRealTimeInsights($lowStockItems, $outOfStockItems);
            $recommendations = $this->generateRealTimeRecommendations($lowStockItems, $outOfStockItems);

            $result = ReportResult::createSuccess(
                $definition,
                $summary,
                $details,
                $insights,
                $recommendations,
                0.0
            );

            $this->logger->info('Real-time inventory report built successfully', [
                'reportId' => $definition->getId(),
                'alertsCount' => count($lowStockItems) + count($outOfStockItems)
            ]);

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Real-time inventory report build failed', [
                'reportId' => $definition->getId(),
                'error' => $e->getMessage()
            ]);

            return ReportResult::createError($definition, $e->getMessage());
        }
    }

    /**
     * Build inventory filters dari report definition
     */
    private function buildInventoryFilters(ReportDefinition $definition): array
    {
        $filters = $definition->getFilters();

        // Apply date range filter jika ada
        if ($dateRange = $definition->getDateRange()) {
            $filters['updatedAt'] = [
                '$gte' => $dateRange->getStartDate(),
                '$lte' => $dateRange->getEndDate()
            ];
        }

        // Apply category filter jika ada
        if (isset($filters['category'])) {
            $filters['categoryId'] = $filters['category'];
            unset($filters['category']);
        }

        // Apply stock level filters
        if (isset($filters['stockLevel'])) {
            switch ($filters['stockLevel']) {
                case 'low':
                    $filters['quantity'] = ['$lt' => 10];
                    break;
                case 'out':
                    $filters['quantity'] = 0;
                    break;
                case 'healthy':
                    $filters['quantity'] = ['$gte' => 10];
                    break;
            }
            unset($filters['stockLevel']);
        }

        return array_filter($filters);
    }

    /**
     * Generate summary statistics dari inventory data
     */
    private function generateSummary(array $inventoryData): array
    {
        if (empty($inventoryData)) {
            return [
                'recordCount' => 0,
                'totalValue' => 0,
                'averagePrice' => 0,
                'lowStockCount' => 0,
                'outOfStockCount' => 0,
                'healthScore' => 0
            ];
        }

        $totalValue = 0;
        $lowStockCount = 0;
        $outOfStockCount = 0;
        $totalItems = count($inventoryData);

        foreach ($inventoryData as $item) {
            $quantity = $item['quantity'] ?? 0;
            $price = $item['price'] ?? 0;
            $minStock = $item['minStockLevel'] ?? 5;

            $totalValue += $quantity * $price;

            if ($quantity === 0) {
                $outOfStockCount++;
            } elseif ($quantity < $minStock) {
                $lowStockCount++;
            }
        }

        $healthScore = $this->calculateInventoryHealth($totalItems, $lowStockCount, $outOfStockCount);

        return [
            'recordCount' => $totalItems,
            'totalValue' => round($totalValue, 2),
            'averagePrice' => $totalItems > 0 ? round($totalValue / $totalItems, 2) : 0,
            'lowStockCount' => $lowStockCount,
            'outOfStockCount' => $outOfStockCount,
            'healthScore' => $healthScore,
            'healthStatus' => $this->getHealthStatus($healthScore)
        ];
    }

    /**
     * Calculate inventory health score (0-100)
     */
    private function calculateInventoryHealth(int $totalItems, int $lowStockCount, int $outOfStockCount): float
    {
        if ($totalItems === 0) return 0;

        $penalty = ($lowStockCount * 10) + ($outOfStockCount * 30);
        $maxPenalty = $totalItems * 30;
        
        $score = max(0, 100 - ($penalty / $maxPenalty * 100));
        return round($score, 1);
    }

    /**
     * Get health status berdasarkan score
     */
    private function getHealthStatus(float $score): string
    {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        if ($score >= 20) return 'poor';
        return 'critical';
    }

    /**
     * Generate AI insights untuk inventory data
     */
    private function generateAIInsights(array $inventoryData): array
    {
        if (empty($inventoryData) || !$this->aiService->isAvailable()) {
            return $this->generateBasicInsights($inventoryData);
        }

        try {
            $analysis = $this->aiService->analyzeInventory($inventoryData, 'health_analysis');
            return $analysis['insights'] ?? $this->generateBasicInsights($inventoryData);
        } catch (\Exception $e) {
            $this->logger->warning('AI insights generation failed, using basic insights', [
                'error' => $e->getMessage()
            ]);
            return $this->generateBasicInsights($inventoryData);
        }
    }

    /**
     * Generate basic insights tanpa AI
     */
    private function generateBasicInsights(array $inventoryData): array
    {
        if (empty($inventoryData)) {
            return [['type' => 'info', 'message' => 'No inventory data available for analysis']];
        }

        $insights = [];
        $summary = $this->generateSummary($inventoryData);

        if ($summary['outOfStockCount'] > 0) {
            $insights[] = [
                'type' => 'critical',
                'message' => sprintf('%d items are out of stock', $summary['outOfStockCount']),
                'priority' => 'high'
            ];
        }

        if ($summary['lowStockCount'] > 0) {
            $insights[] = [
                'type' => 'warning',
                'message' => sprintf('%d items are low on stock', $summary['lowStockCount']),
                'priority' => 'medium'
            ];
        }

        if ($summary['healthScore'] >= 80) {
            $insights[] = [
                'type' => 'positive',
                'message' => 'Inventory health is excellent',
                'priority' => 'low'
            ];
        }

        return $insights;
    }

    /**
     * Generate recommendations berdasarkan insights
     */
    private function generateRecommendations(array $summary, array $insights): array
    {
        $recommendations = [];

        if ($summary['outOfStockCount'] > 0) {
            $recommendations[] = [
                'type' => 'restock',
                'priority' => 'high',
                'action' => 'Immediate restock required for out-of-stock items',
                'impact' => 'Prevent lost sales'
            ];
        }

        if ($summary['lowStockCount'] > 0) {
            $recommendations[] = [
                'type' => 'monitor',
                'priority' => 'medium',
                'action' => 'Monitor low-stock items and plan restocking',
                'impact' => 'Maintain optimal inventory levels'
            ];
        }

        if ($summary['healthScore'] < 60) {
            $recommendations[] = [
                'type' => 'optimize',
                'priority' => 'medium',
                'action' => 'Review inventory management practices',
                'impact' => 'Improve overall inventory health'
            ];
        }

        return $recommendations;
    }

    // Stub methods untuk advanced features (akan diimplementasi lengkap nanti)
    private function analyzeComparativeData(array $currentSummary, array $comparisonData): array
    {
        return [['type' => 'info', 'message' => 'Comparative analysis will be available in next phase']];
    }

    private function generatePredictiveSummary(array $currentData, array $predictions, int $forecastDays): array
    {
        return [
            'recordCount' => count($predictions),
            'forecastPeriod' => $forecastDays,
            'predictionsGenerated' => count($predictions),
            'confidenceLevel' => 'medium'
        ];
    }

    private function generatePredictiveInsights(array $predictions): array
    {
        return [['type' => 'info', 'message' => 'Predictive insights will be available in next phase']];
    }

    private function generatePredictiveRecommendations(array $predictions): array
    {
        return [['type' => 'info', 'message' => 'Predictive recommendations will be available in next phase']];
    }

    private function calculateAlertLevel(array $lowStockItems, array $outOfStockItems): string
    {
        $totalAlerts = count($lowStockItems) + count($outOfStockItems);
        
        if ($totalAlerts === 0) return 'normal';
        if (count($outOfStockItems) > 0) return 'high';
        if (count($lowStockItems) > 5) return 'medium';
        return 'low';
    }

    private function generateRealTimeInsights(array $lowStockItems, array $outOfStockItems): array
    {
        $insights = [];

        if (!empty($outOfStockItems)) {
            $insights[] = [
                'type' => 'critical',
                'message' => 'Immediate attention required for out-of-stock items',
                'priority' => 'high'
            ];
        }

        if (!empty($lowStockItems)) {
            $insights[] = [
                'type' => 'warning', 
                'message' => 'Low stock items need monitoring',
                'priority' => 'medium'
            ];
        }

        if (empty($lowStockItems) && empty($outOfStockItems)) {
            $insights[] = [
                'type' => 'positive',
                'message' => 'All inventory levels are healthy',
                'priority' => 'low'
            ];
        }

        return $insights;
    }

    private function generateRealTimeRecommendations(array $lowStockItems, array $outOfStockItems): array
    {
        $recommendations = [];

        if (!empty($outOfStockItems)) {
            $recommendations[] = [  // PERBAIKAN: gunakan [] bukan {}
                'type' => 'restock',
                'priority' => 'high',
                'action' => 'Restock out-of-stock items immediately',
                'timeline' => 'within 24 hours'
            ];
        }

        if (!empty($lowStockItems)) {
            $recommendations[] = [  // PERBAIKAN: gunakan [] bukan {}
                'type' => 'review',
                'priority' => 'medium', 
                'action' => 'Review low-stock items and plan restocking',
                'timeline' => 'within 7 days'
            ];
        }

        return $recommendations;
    }

}