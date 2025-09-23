<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Interface untuk AI Service
 */
interface IAIService
{
    /**
     * Analyze inventory data dengan AI
     */
    public function analyzeInventory(array $inventoryData, string $analysisType = 'stock_prediction'): array;

    /**
     * Generate laporan AI berdasarkan data inventory
     */
    public function generateReport(array $inventoryData, string $reportType = 'summary'): array;

    /**
     * Predict stock needs untuk items tertentu
     */
    public function predictStockNeeds(array $items, int $forecastDays = 30): array;

    /**
     * Detect anomalies dalam inventory data
     */
    public function detectAnomalies(array $inventoryData): array;

    /**
     * Set active AI strategy
     */
    public function setStrategy(string $strategyName): bool;

    /**
     * Get available AI strategies
     */
    public function getAvailableStrategies(): array;

    /**
     * Check if AI service is available
     */
    public function isAvailable(): bool;
}