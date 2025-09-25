<?php
// File: src/Service/AIService.php (Perbaikan use statement)
declare(strict_types=1);

namespace App\Service;

// PERBAIKAN: Hapus \AIStrategy dari use statement
use App\Utility\Logger;
use InvalidArgumentException;
use RuntimeException;

class AIService implements IAIService, IAdvancedAIService
{
    private array $strategies = [];
    private ?AIStrategy $activeStrategy = null; // Ini akan merujuk ke App\Service\AIStrategy
    private Logger $logger;
    private bool $enabled;

    public function __construct(Logger $logger, bool $enabled = true)
    {
        $this->logger = $logger;
        $this->enabled = $enabled;
        
        $this->logger->info('AIService initialized', [
            'enabled' => $enabled,
            'timestamp' => date('c')
        ]);
    }

    public function registerStrategy(string $name, AIStrategy $strategy): void
    {
        $this->strategies[$name] = $strategy;
        
        // Set active strategy jika belum ada
        if ($this->activeStrategy === null) {
            $this->activeStrategy = $strategy;
            $this->logger->info("Default AI strategy set: {$name}");
        }
        
        $this->logger->info('AI Strategy registered', [
            'strategy_name' => $name,
            'total_strategies' => count($this->strategies)
        ]);
    }

    // ========== SESSION 1.2: ADVANCED ANALYSIS METHODS ==========

    public function analyzeSalesTrends(array $salesData, int $periodDays = 30): array
    {
        // Validasi parameter pertama - sangat ketat
        if (!is_array($salesData)) {
            throw new InvalidArgumentException(
                'Sales data must be an array, ' . gettype($salesData) . ' given'
            );
        }

        // Validasi empty array
        if (empty($salesData)) {
            throw new InvalidArgumentException('Sales data cannot be empty');
        }

        // Validasi structure
        $this->validateSalesData($salesData);

        $this->logger->info('AIService: Analyzing sales trends', [
            'data_points' => count($salesData),
            'period_days' => $periodDays
        ]);

        if (!$this->isAvailable()) {
            return $this->getFallbackSalesTrends($salesData, $periodDays);
        }

        try {
            $analysisData = [
                'sales_data' => $salesData,
                'period_days' => $periodDays,
                'analysis_timestamp' => date('c')
            ];

            $result = $this->activeStrategy->analyze($analysisData, 'sales_trends');
            
            return array_merge([
                'analysis_type' => 'sales_trends',
                'timestamp' => date('c'),
                'is_fallback' => false
            ], $result);

        } catch (\Exception $e) {
            $this->logger->error('AIService: Sales trend analysis failed', [
                'error' => $e->getMessage(),
                'fallback_used' => true
            ]);
            
            return $this->getFallbackSalesTrends($salesData, $periodDays);
        }
    }

    public function predictInventoryTurnover(array $items): array
    {
        $this->logger->info('AIService: Predicting inventory turnover', [
            'items_count' => count($items)
        ]);

        if (!$this->isAvailable()) {
            return $this->getFallbackTurnoverPrediction($items);
        }

        try {
            $this->validateInventoryItems($items);
            
            $analysisData = [
                'items' => $items,
                'analysis_timestamp' => date('c')
            ];

            return $this->activeStrategy->analyze($analysisData, 'inventory_turnover');
        } catch (\Exception $e) {
            $this->logger->error('AIService: Inventory turnover prediction failed', [
                'error' => $e->getMessage(),
                'fallback_used' => true
            ]);
            
            return $this->getFallbackTurnoverPrediction($items);
        }
    }

    public function optimizeStockLevels(array $inventoryData): array
    {
        $this->logger->info('AIService: Optimizing stock levels', [
            'inventory_items' => count($inventoryData)
        ]);

        if (!$this->isAvailable()) {
            $result = $this->getFallbackStockOptimization($inventoryData);
            $this->logger->warning('AIService: Using fallback for stock optimization');
            return $result;
        }

        try {
            $this->validateStockOptimizationData($inventoryData);
            
            $analysisData = [
                'inventory' => $inventoryData,
                'analysis_timestamp' => date('c')
            ];

            $result = $this->activeStrategy->analyze($analysisData, 'stock_optimization');
            
            // PERBAIKAN: Pastikan struktur return konsisten
            if (!isset($result['optimizations'])) {
                $result['optimizations'] = [];
            }
            
            return array_merge([
                'analysis_type' => 'stock_optimization',
                'timestamp' => date('c'),
                'is_fallback' => false
            ], $result);

        } catch (\Exception $e) {
            $this->logger->error('AIService: Stock optimization failed', [
                'error' => $e->getMessage(),
                'fallback_used' => true
            ]);
            
            return $this->getFallbackStockOptimization($inventoryData);
        }
    }

    public function generatePurchaseRecommendations(array $supplierData): array
    {
        $this->logger->info('AIService: Generating purchase recommendations', [
            'suppliers_count' => count($supplierData)
        ]);

        if (!$this->isAvailable()) {
            return $this->getFallbackPurchaseRecommendations($supplierData);
        }

        try {
            $this->validateSupplierData($supplierData);
            
            $analysisData = [
                'suppliers' => $supplierData,
                'analysis_timestamp' => date('c')
            ];

            return $this->activeStrategy->analyze($analysisData, 'purchase_recommendations');
        } catch (\Exception $e) {
            $this->logger->error('AIService: Purchase recommendations failed', [
                'error' => $e->getMessage(),
                'fallback_used' => true
            ]);
            
            return $this->getFallbackPurchaseRecommendations($supplierData);
        }
    }

    public function calculateSafetyStock(array $itemHistory): array
    {
        $this->logger->info('AIService: Calculating safety stock', [
            'history_entries' => count($itemHistory)
        ]);

        if (!$this->isAvailable()) {
            return $this->getFallbackSafetyStock($itemHistory);
        }

        try {
            $this->validateItemHistory($itemHistory);
            
            $analysisData = [
                'item_history' => $itemHistory,
                'analysis_timestamp' => date('c')
            ];

            return $this->activeStrategy->analyze($analysisData, 'safety_stock');
        } catch (\Exception $e) {
            $this->logger->error('AIService: Safety stock calculation failed', [
                'error' => $e->getMessage(),
                'fallback_used' => true
            ]);
            
            return $this->getFallbackSafetyStock($itemHistory);
        }
    }

    // ========== VALIDATION METHODS ==========

    private function validateSalesData(array $salesData): void
    {
        // Validasi 1: Pastikan bukan empty array
        if (empty($salesData)) {
            throw new InvalidArgumentException('Sales data cannot be empty');
        }

        // Validasi 2: Pastikan setiap element adalah array
        foreach ($salesData as $index => $sale) {
            if (!is_array($sale)) {
                throw new InvalidArgumentException(
                    "Sales data at index {$index} must be an array, " . gettype($sale) . " given"
                );
            }

            // Validasi 3: Field yang required
            $requiredFields = ['date', 'quantity', 'revenue'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!array_key_exists($field, $sale)) {
                    $missingFields[] = $field;
                }
            }

            if (!empty($missingFields)) {
                throw new InvalidArgumentException(
                    "Invalid sales data structure at index {$index}. Missing fields: " . 
                    implode(', ', $missingFields) . ". Available fields: " . 
                    implode(', ', array_keys($sale))
                );
            }

            // Validasi 4: Tipe data dan nilai
            if (!is_numeric($sale['quantity']) || $sale['quantity'] < 0) {
                throw new InvalidArgumentException(
                    "Invalid quantity at index {$index}: must be positive number, got " . 
                    var_export($sale['quantity'], true)
                );
            }

            if (!is_numeric($sale['revenue']) || $sale['revenue'] < 0) {
                throw new InvalidArgumentException(
                    "Invalid revenue at index {$index}: must be positive number, got " . 
                    var_export($sale['revenue'], true)
                );
            }

            // Validasi 5: Date format (basic validation)
            if (empty($sale['date']) || !is_string($sale['date'])) {
                throw new InvalidArgumentException(
                    "Invalid date at index {$index}: must be non-empty string"
                );
            }
        }
    }

    private function validateInventoryItems(array $items): void
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Inventory items cannot be empty');
        }

        foreach ($items as $index => $item) {
            $requiredFields = ['name', 'currentStock', 'salesHistory'];
            foreach ($requiredFields as $field) {
                if (!isset($item[$field])) {
                    throw new InvalidArgumentException(
                        "Missing required field '{$field}' at index {$index}"
                    );
                }
            }

            // Validasi salesHistory
            if (!is_array($item['salesHistory'])) {
                throw new InvalidArgumentException(
                    "salesHistory must be an array at index {$index}"
                );
            }
        }
    }

    private function validateStockOptimizationData(array $inventoryData): void
    {
        if (empty($inventoryData)) {
            throw new InvalidArgumentException('Inventory data cannot be empty');
        }

        foreach ($inventoryData as $index => $item) {
            $requiredFields = ['name', 'currentStock', 'minStock', 'maxStock', 'leadTimeDays'];
            foreach ($requiredFields as $field) {
                if (!isset($item[$field])) {
                    throw new InvalidArgumentException(
                        "Missing required field '{$field}' for stock optimization at index {$index}"
                    );
                }
            }

            if (!is_numeric($item['currentStock']) || $item['currentStock'] < 0) {
                throw new InvalidArgumentException(
                    "Invalid currentStock at index {$index}: must be positive number"
                );
            }
        }
    }

    private function validateSupplierData(array $supplierData): void
    {
        if (empty($supplierData)) {
            throw new InvalidArgumentException('Supplier data cannot be empty');
        }

        foreach ($supplierData as $index => $supplier) {
            if (!isset($supplier['name']) || !isset($supplier['leadTimeDays']) || !isset($supplier['reliabilityScore'])) {
                throw new InvalidArgumentException(
                    "Invalid supplier data at index {$index}. Required fields: name, leadTimeDays, reliabilityScore"
                );
            }
        }
    }

    private function validateItemHistory(array $itemHistory): void
    {
        if (empty($itemHistory)) {
            throw new InvalidArgumentException('Item history cannot be empty');
        }

        foreach ($itemHistory as $index => $history) {
            if (!isset($history['date']) || !isset($history['demand']) || !isset($history['leadTime'])) {
                throw new InvalidArgumentException(
                    "Invalid item history at index {$index}. Required fields: date, demand, leadTime"
                );
            }
        }
    }

    // ========== FALLBACK METHODS ==========

    private function getFallbackSalesTrends(array $salesData, int $periodDays): array
    {
        $this->logger->warning('AIService: Using fallback sales trends analysis');
        
        $totalSales = 0;
        $totalRevenue = 0;
        $dates = [];

        foreach ($salesData as $sale) {
            $quantity = $sale['quantity'] ?? 0;
            $revenue = $sale['revenue'] ?? 0;
            
            $totalSales += $quantity;
            $totalRevenue += $revenue;
            $dates[] = strtotime($sale['date'] ?? 'now');
        }

        $averageDailySales = $totalSales / max(1, $periodDays);
        $growthRate = $this->calculateSimpleGrowthRate($salesData);

        return [
            'analysis_type' => 'sales_trends',
            'trend' => $growthRate > 0 ? 'increasing' : ($growthRate < 0 ? 'decreasing' : 'stable'),
            'growth_rate' => round($growthRate, 4),
            'average_daily_sales' => round($averageDailySales, 2),
            'total_period_sales' => $totalSales,
            'total_revenue' => round($totalRevenue, 2),
            'confidence' => 0.6,
            'is_fallback' => true,
            'recommendations' => [
                'Consider manual analysis for more accurate trends',
                'Monitor sales data for pattern changes'
            ],
            'timestamp' => date('c')
        ];
    }

    private function getFallbackTurnoverPrediction(array $items): array
    {
        $this->logger->warning('AIService: Using fallback turnover prediction');
        
        $predictions = [];
        $totalTurnover = 0;

        foreach ($items as $item) {
            $salesHistory = $item['salesHistory'] ?? [];
            $currentStock = $item['currentStock'] ?? 0;
            
            if (count($salesHistory) > 0 && $currentStock > 0) {
                $avgSales = array_sum(array_column($salesHistory, 'quantity')) / count($salesHistory);
                $turnoverRate = $avgSales > 0 ? $currentStock / $avgSales : 0;
                
                $predictions[$item['name']] = [
                    'predicted_turnover_days' => round($turnoverRate, 2),
                    'confidence' => 0.5,
                    'basis' => 'historical_average'
                ];
                
                $totalTurnover += $turnoverRate;
            }
        }

        $averageTurnover = count($predictions) > 0 ? $totalTurnover / count($predictions) : 0;

        return [
            'predictions' => $predictions,
            'average_turnover_days' => round($averageTurnover, 2),
            'is_fallback' => true,
            'risk_assessment' => 'medium',
            'notes' => 'Based on simple historical average calculation'
        ];
    }

    private function getFallbackStockOptimization(array $inventoryData): array
    {
        $this->logger->warning('AIService: Using fallback stock optimization');
        
        $optimizations = [];
        $totalSavings = 0;
        $itemsProcessed = 0;

        foreach ($inventoryData as $item) {
            if (!is_array($item) || !isset($item['name'])) {
                continue; // Skip invalid items
            }

            $currentStock = $item['currentStock'] ?? 0;
            $minStock = $item['minStock'] ?? 0;
            $maxStock = $item['maxStock'] ?? max($currentStock * 2, $minStock + 10);
            $leadTime = $item['leadTimeDays'] ?? 7;
            $dailyUsage = $item['dailyUsage'] ?? ($currentStock > 0 ? $currentStock / 30 : 1);
            $unitCost = $item['unitCost'] ?? 1;

            // Calculations
            $safetyStock = max(0, $dailyUsage * $leadTime * 1.5);
            $reorderPoint = max(0, $dailyUsage * $leadTime + $safetyStock);
            $optimalStock = min($maxStock, max($minStock, $reorderPoint * 1.2));

            $currentCost = $currentStock * $unitCost;
            $optimalCost = $optimalStock * $unitCost;
            $savings = $currentCost - $optimalCost;

            $optimizations[$item['name']] = [
                'current_stock' => $currentStock,
                'optimal_stock' => round($optimalStock, 2),
                'safety_stock' => round($safetyStock, 2),
                'reorder_point' => round($reorderPoint, 2),
                'potential_savings' => max(0, round($savings, 2)),
                'unit_cost' => $unitCost
            ];

            $totalSavings += max(0, $savings);
            $itemsProcessed++;
        }

        return [
            'analysis_type' => 'stock_optimization',
            'optimizations' => $optimizations,
            'total_potential_savings' => round($totalSavings, 2),
            'items_processed' => $itemsProcessed,
            'is_fallback' => true,
            'optimization_method' => 'basic_rule_based',
            'timestamp' => date('c'),
            'confidence' => 0.7
        ];
    }

    private function getFallbackPurchaseRecommendations(array $supplierData): array
    {
        $this->logger->warning('AIService: Using fallback purchase recommendations');
        
        $recommendations = [];
        $totalScore = 0;

        foreach ($supplierData as $supplier) {
            $score = $this->calculateSupplierScore($supplier);
            $recommendation = $score >= 0.7 ? 'highly_recommended' : 
                            ($score >= 0.5 ? 'recommended' : 'not_recommended');

            $recommendations[] = [
                'supplier_name' => $supplier['name'],
                'score' => $score,
                'recommendation' => $recommendation,
                'lead_time_days' => $supplier['leadTimeDays'],
                'reliability' => $supplier['reliabilityScore']
            ];

            $totalScore += $score;
        }

        // Sort by score descending
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'recommendations' => $recommendations,
            'average_score' => count($recommendations) > 0 ? $totalScore / count($recommendations) : 0,
            'is_fallback' => true,
            'evaluation_criteria' => [
                'lead_time' => 'weighted_importance',
                'reliability' => 'primary_factor',
                'cost' => 'considered_if_available'
            ]
        ];
    }

    private function getFallbackSafetyStock(array $itemHistory): array
    {
        $this->logger->warning('AIService: Using fallback safety stock calculation');
        
        if (empty($itemHistory)) {
            return [
                'safety_stock' => 0,
                'confidence' => 0,
                'is_fallback' => true,
                'error' => 'Insufficient data for calculation'
            ];
        }

        $demands = array_column($itemHistory, 'demand');
        $leadTimes = array_column($itemHistory, 'leadTime');

        $avgDemand = array_sum($demands) / count($demands);
        $avgLeadTime = array_sum($leadTimes) / count($leadTimes);
        $stdDevDemand = $this->calculateStandardDeviation($demands);

        // Basic safety stock formula: Z-score * std.dev * sqrt(lead_time)
        $safetyStock = 1.65 * $stdDevDemand * sqrt($avgLeadTime); // 90% service level

        return [
            'safety_stock' => max(0, round($safetyStock, 2)),
            'average_demand' => round($avgDemand, 2),
            'average_lead_time' => round($avgLeadTime, 2),
            'demand_std_dev' => round($stdDevDemand, 2),
            'service_level' => 0.9,
            'confidence' => 0.7,
            'is_fallback' => true,
            'formula_used' => 'z_score * std_dev_demand * sqrt(avg_lead_time)'
        ];
    }

    // ========== HELPER METHODS ==========

    private function calculateSimpleGrowthRate(array $salesData): float
    {
        if (count($salesData) < 2) {
            return 0.0;
        }

        $firstSale = $salesData[0];
        $lastSale = end($salesData);

        $firstQuantity = $firstSale['quantity'] ?? 0;
        $lastQuantity = $lastSale['quantity'] ?? 0;

        if ($firstQuantity == 0) {
            return 0.0;
        }

        return ($lastQuantity - $firstQuantity) / $firstQuantity;
    }


    private function calculateSupplierScore(array $supplier): float
    {
        $leadTime = $supplier['leadTimeDays'] ?? 30;
        $reliability = $supplier['reliabilityScore'] ?? 0.5;
        $cost = $supplier['costScore'] ?? 0.5;

        // Weighted scoring: reliability 50%, lead time 30%, cost 20%
        $leadTimeScore = max(0, 1 - ($leadTime / 60)); // Normalize lead time (max 60 days)
        $reliabilityScore = $reliability;
        $costScore = $cost;

        return (0.5 * $reliabilityScore) + (0.3 * $leadTimeScore) + (0.2 * $costScore);
    }

    private function calculateStandardDeviation(array $numbers): float
    {
        $n = count($numbers);
        if ($n <= 1) {
            return 0.0;
        }

        $mean = array_sum($numbers) / $n;
        $sumSquares = 0.0;

        foreach ($numbers as $number) {
            $sumSquares += pow($number - $mean, 2);
        }

        return sqrt($sumSquares / ($n - 1));
    }

    // ========== SESSION 1.1: EXISTING METHODS ==========

    public function analyzeInventory(array $inventoryData, string $analysisType = 'stock_prediction'): array
    {
        if (!$this->enabled) {
            return $this->getFallbackAnalysis($inventoryData, $analysisType);
        }

        try {
            $this->validateInventoryData($inventoryData);
            
            if ($this->activeStrategy === null) {
                throw new RuntimeException('No active AI strategy available');
            }

            $this->logger->info("AI analysis started", [
                'analysis_type' => $analysisType,
                'items_count' => count($inventoryData['items'] ?? []),
                'strategy' => get_class($this->activeStrategy)
            ]);

            $result = $this->activeStrategy->analyze($inventoryData, $analysisType);
            
            $this->logger->info("AI analysis completed", [
                'analysis_type' => $analysisType,
                'risk_level' => $result['riskLevel'] ?? 'unknown',
                'confidence' => $result['confidence'] ?? 0
            ]);

            return $result;

        } catch (RuntimeException $e) {
            $this->logger->error("AI analysis failed: " . $e->getMessage(), [
                'analysis_type' => $analysisType,
                'fallback_used' => true
            ]);
            return $this->getFallbackAnalysis($inventoryData, $analysisType);
        }
    }

    public function generateReport(array $inventoryData, string $reportType = 'summary'): array
    {
        if (!$this->enabled || $this->activeStrategy === null) {
            return $this->getFallbackReport($inventoryData, $reportType);
        }

        try {
            $this->validateInventoryData($inventoryData);

            $this->logger->info("AI report generation started", [
                'report_type' => $reportType,
                'strategy' => get_class($this->activeStrategy)
            ]);

            $report = $this->activeStrategy->generate($inventoryData, $reportType);
            
            $this->logger->info("AI report generated", [
                'report_type' => $reportType,
                'sections_count' => count($report['sections'] ?? [])
            ]);

            return $report;

        } catch (RuntimeException $e) {
            $this->logger->error("AI report generation failed: " . $e->getMessage());
            return $this->getFallbackReport($inventoryData, $reportType);
        }
    }

    public function predictStockNeeds(array $items, int $forecastDays = 30): array
    {
        $inventoryData = [
            'items' => $items,
            'analysisType' => 'stock_prediction',
            'forecastDays' => $forecastDays
        ];

        return $this->analyzeInventory($inventoryData, 'stock_prediction');
    }

    public function detectAnomalies(array $inventoryData): array
    {
        return $this->analyzeInventory($inventoryData, 'anomaly_detection');
    }

    public function setStrategy(string $strategyName): bool
    {
        if (!isset($this->strategies[$strategyName])) {
            $this->logger->error("AI strategy not found: {$strategyName}", ['available_strategies' => array_keys($this->strategies)]);
            return false;
        }

        $this->activeStrategy = $this->strategies[$strategyName];
        $this->logger->info("AI strategy changed to: {$strategyName}");
        return true;
    }

    public function getAvailableStrategies(): array
    {
        return array_keys($this->strategies);
    }

    public function isAvailable(): bool
    {
        return $this->enabled && $this->activeStrategy !== null;
    }

    private function validateInventoryData(array $data): void
    {
        if (!isset($data['items']) || !is_array($data['items'])) {
            throw new InvalidArgumentException('Inventory data must contain items array');
        }

        if (empty($data['items'])) {
            throw new InvalidArgumentException('Inventory data items cannot be empty');
        }

        // Validasi setiap item
        foreach ($data['items'] as $index => $item) {
            if (!isset($item['name']) || empty(trim($item['name']))) {
                throw new InvalidArgumentException("Item at index {$index} must have a name");
            }
            
            if (!isset($item['quantity']) || !is_numeric($item['quantity'])) {
                throw new InvalidArgumentException("Item {$item['name']} must have a numeric quantity");
            }
        }
    }

    private function getFallbackAnalysis(array $inventoryData, string $analysisType): array
    {
        $this->logger->warning("Using fallback analysis", ['analysis_type' => $analysisType]);

        $items = $inventoryData['items'] ?? [];
        $criticalItems = array_filter($items, fn($item) => ($item['quantity'] ?? 0) <= ($item['minStockLevel'] ?? 0));
        $outOfStockItems = array_filter($items, fn($item) => ($item['quantity'] ?? 0) === 0);

        $baseAnalysis = [
            'analysis' => 'Basic inventory analysis',
            'riskLevel' => empty($criticalItems) ? 'low' : 'high',
            'confidence' => 0.7,
            'generatedBy' => 'fallback_algorithm',
            'timestamp' => date('c')
        ];

        switch ($analysisType) {
            case 'stock_prediction':
                return array_merge($baseAnalysis, [
                    'recommendations' => $this->generateStockRecommendations($items),
                    'criticalItemsCount' => count($criticalItems),
                    'outOfStockCount' => count($outOfStockItems)
                ]);

            case 'anomaly_detection':
                return array_merge($baseAnalysis, [
                    'anomalies' => $this->detectBasicAnomalies($items),
                    'analysisType' => 'anomaly_detection'
                ]);

            default:
                return $baseAnalysis;
        }
    }

    private function getFallbackReport(array $inventoryData, string $reportType): array
    {
        $items = $inventoryData['items'] ?? [];

        return [
            'reportType' => $reportType,
            'summary' => [
                'totalItems' => count($items),
                'totalValue' => array_sum(array_map(fn($item) => ($item['quantity'] ?? 0) * ($item['price'] ?? 0), $items)),
                'criticalItems' => count(array_filter($items, fn($item) => ($item['quantity'] ?? 0) <= ($item['minStockLevel'] ?? 0)))
            ],
            'generatedBy' => 'fallback_algorithm',
            'timestamp' => date('c')
        ];
    }

    private function generateStockRecommendations(array $items): array
    {
        $recommendations = [];
        
        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? 0;
            $minStock = $item['minStockLevel'] ?? 0;
            $name = $item['name'] ?? 'Unknown';
            
            if ($quantity <= $minStock) {
                $needed = max($minStock * 2 - $quantity, 10); // Restock to 2x min level atau min 10
                $recommendations[] = "Restock {$name}: {$needed} units needed (current: {$quantity})";
            }
        }

        if (empty($recommendations)) {
            $recommendations[] = "Stock levels are adequate for all items";
        }

        return $recommendations;
    }    

    private function detectBasicAnomalies(array $items): array
    {
        $anomalies = [];
        
        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? 0;
            $price = $item['price'] ?? 0;
            
            // Detect potential anomalies
            if ($quantity < 0) {
                $anomalies[] = "Negative quantity for {$item['name']}";
            }
            
            if ($price < 0) {
                $anomalies[] = "Negative price for {$item['name']}";
            }
            
            if ($quantity > 10000) {
                $anomalies[] = "Unusually high quantity for {$item['name']}";
            }
        }

        return $anomalies;
    }

    // ========== SESSION 1.2: ADVANCED ANALYSIS METHODS Bagian 2 ==========
        // Tambahkan method ini ke AIService class
        public function analyzeInventoryWithStats(array $inventoryData, string $analysisType = 'comprehensive'): array
        {
            $this->logger->info('AIService: Analyzing inventory with stats', [
                'analysis_type' => $analysisType,
                'items_count' => count($inventoryData['items'] ?? [])
            ]);

            if (!$this->isAvailable()) {
                return $this->getFallbackInventoryAnalysis($inventoryData, $analysisType);
            }

            try {
                $analysisData = array_merge($inventoryData, [
                    'analysis_timestamp' => date('c'),
                    'analysis_type' => $analysisType
                ]);

                return $this->activeStrategy->analyze($analysisData, $analysisType);
            } catch (\Exception $e) {
                $this->logger->error('Inventory analysis with stats failed', [
                    'error' => $e->getMessage()
                ]);
                
                return $this->getFallbackInventoryAnalysis($inventoryData, $analysisType);
            }
        }

        private function getFallbackInventoryAnalysis(array $inventoryData, string $analysisType): array
        {
            $items = $inventoryData['items'] ?? [];
            $stats = $inventoryData['stats'] ?? [];
            
            return [
                'analysis_type' => $analysisType,
                'riskLevel' => 'medium',
                'confidence' => 0.6,
                'recommendations' => ['Review inventory levels manually'],
                'is_fallback' => true,
                'timestamp' => date('c')
            ];
        }

    // ========== ========== ==========
}
?>