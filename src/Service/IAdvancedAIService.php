<?php
// File: src/Service/IAdvancedAIService.php
declare(strict_types=1);

namespace App\Service;

interface IAdvancedAIService
{
    /**
     * Analyze sales trends from historical data
     * 
     * @param array $salesData Historical sales data with date, quantity, revenue
     * @param int $periodDays Analysis period in days
     * @return array Trend analysis with growth rates and predictions
     */
    public function analyzeSalesTrends(array $salesData, int $periodDays = 30): array;

    /**
     * Predict inventory turnover rates for items
     * 
     * @param array $items Inventory items with sales history
     * @return array Turnover predictions and risk assessment
     */
    public function predictInventoryTurnover(array $items): array;

    /**
     * Optimize stock levels based on demand patterns
     * 
     * @param array $inventoryData Current inventory levels and constraints
     * @return array Optimal stock levels and cost savings
     */
    public function optimizeStockLevels(array $inventoryData): array;

    /**
     * Generate purchase recommendations based on supplier data
     * 
     * @param array $supplierData Supplier performance and lead times
     * @return array Purchase recommendations and supplier scores
     */
    public function generatePurchaseRecommendations(array $supplierData): array;

    /**
     * Calculate safety stock levels based on demand variability
     * 
     * @param array $itemHistory Historical demand and lead time data
     * @return array Safety stock calculations and confidence levels
     */
    public function calculateSafetyStock(array $itemHistory): array;
}
?>