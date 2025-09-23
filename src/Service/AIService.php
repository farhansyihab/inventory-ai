<?php
declare(strict_types=1);

namespace App\Service;

use App\Service\AIStrategy;
use App\Utility\Logger;
use InvalidArgumentException;
use RuntimeException;

class AIService implements IAIService
{
    private array $strategies = [];
    private ?AIStrategy $activeStrategy = null;
    private Logger $logger;
    private bool $enabled;

    public function __construct(Logger $logger, bool $enabled = true)
    {
        $this->logger = $logger;
        $this->enabled = $enabled;
        
        if (!$this->enabled) {
            $this->logger->warning('AIService initialized in disabled mode');
        }
    }

    public function registerStrategy(string $name, AIStrategy $strategy): void
    {
        $this->strategies[$name] = $strategy;
        $this->logger->info("AI strategy registered: {$name}", ['strategy_class' => get_class($strategy)]);
        
        // Set first strategy as active jika belum ada
        if ($this->activeStrategy === null) {
            $this->activeStrategy = $strategy;
            $this->logger->info("Default AI strategy set: {$name}");
        }
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
}