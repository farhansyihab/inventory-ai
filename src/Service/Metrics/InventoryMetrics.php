<?php
// src/Service/Metrics/InventoryMetrics.php

namespace App\Service\Metrics;

use App\Service\InventoryService;
use App\Exception\DashboardException;
use Psr\Log\LoggerInterface;

class InventoryMetrics
{
    private InventoryService $inventoryService;
    private LoggerInterface $logger;

    public function __construct(InventoryService $inventoryService, LoggerInterface $logger)
    {
        $this->inventoryService = $inventoryService;
        $this->logger = $logger;
    }

    public function getInventoryMetrics(bool $detailed = false): array
    {
        try {
            $this->logger->info('Collecting inventory metrics', ['detailed' => $detailed]);

            // Basic counts
            $totalItems = $this->inventoryService->count(['deleted' => false]);
            $lowStockItems = $this->inventoryService->getLowStockItems();
            $outOfStockItems = $this->inventoryService->getOutOfStockItems();
            $inventoryStats = $this->inventoryService->getStats();

            $metrics = [
                'overview' => [
                    'totalItems' => $totalItems,
                    'categoriesCount' => $inventoryStats['categoriesCount'] ?? 0,
                    'suppliersCount' => $inventoryStats['suppliersCount'] ?? 0,
                ],
                'stockLevels' => [
                    'optimal' => $totalItems - count($lowStockItems) - count($outOfStockItems),
                    'lowStockCount' => count($lowStockItems),
                    'outOfStockCount' => count($outOfStockItems),
                    'overStock' => $inventoryStats['overStockCount'] ?? 0,
                ],
                'healthStatus' => MetricsCalculator::calculateHealthStatus(
                    count($lowStockItems),
                    count($outOfStockItems),
                    $totalItems
                )
            ];

            if ($detailed) {
                $metrics['valueAnalysis'] = $this->getValueAnalysis();
                $metrics['movement'] = $this->getMovementMetrics();
            }

            $this->logger->info('Inventory metrics collected successfully', [
                'totalItems' => $totalItems,
                'lowStock' => count($lowStockItems),
                'outOfStock' => count($outOfStockItems)
            ]);

            return $metrics;

        } catch (\Exception $e) {
            $this->logger->error('Failed to collect inventory metrics', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw DashboardException::serviceUnavailable('InventoryService', $e);
        }
    }

    private function getValueAnalysis(): array
    {
        // This would typically come from InventoryService::getValueAnalysis()
        // For now, returning mock data structure
        return [
            'totalValue' => 0.0,
            'averageItemValue' => 0.0,
            'highestValueItem' => null
        ];
    }

    private function getMovementMetrics(): array
    {
        // This would typically come from InventoryService::getMovementMetrics()
        return [
            'itemsAddedToday' => 0,
            'itemsSoldToday' => 0,
            'restockNeeded' => 0
        ];
    }

    public function getInventoryAlerts(): array
    {
        $metrics = $this->getInventoryMetrics();
        $alerts = [];

        $outOfStockRatio = $metrics['stockLevels']['outOfStockCount'] / max(1, $metrics['overview']['totalItems']) * 100;
        $lowStockRatio = $metrics['stockLevels']['lowStockCount'] / max(1, $metrics['overview']['totalItems']) * 100;

        if ($outOfStockRatio > 5) {
            $alerts[] = [
                'type' => 'inventory',
                'level' => 'critical',
                'title' => 'High Out-of-Stock Items',
                'message' => sprintf('%d items are out of stock (%.1f%%)', 
                    $metrics['stockLevels']['outOfStockCount'], $outOfStockRatio),
                'actionUrl' => '/inventory?filter=out_of_stock'
            ];
        } elseif ($outOfStockRatio > 2) {
            $alerts[] = [
                'type' => 'inventory',
                'level' => 'warning',
                'title' => 'Out-of-Stock Items',
                'message' => sprintf('%d items need restocking', $metrics['stockLevels']['outOfStockCount']),
                'actionUrl' => '/inventory?filter=out_of_stock'
            ];
        }

        if ($lowStockRatio > 10) {
            $alerts[] = [
                'type' => 'inventory',
                'level' => 'warning',
                'title' => 'High Low-Stock Items',
                'message' => sprintf('%d items are low on stock', $metrics['stockLevels']['lowStockCount']),
                'actionUrl' => '/inventory?filter=low_stock'
            ];
        }

        return $alerts;
    }
}