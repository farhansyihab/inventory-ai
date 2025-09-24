<?php
// File: tests/Unit/AI/AIServiceAdvancedTest.php (Perbaikan test cases)
declare(strict_types=1);

namespace Tests\Unit\AI;

use App\Service\AIService;
use App\Service\AIStrategy;
use App\Utility\Logger;
use PHPUnit\Framework\TestCase;

class MockAIStrategy implements AIStrategy
{
    public function analyze(array $data, string $analysisType = 'stock_prediction'): array
    {
        // PERBAIKAN: Return struktur yang sesuai dengan analysis type
        $baseResult = [
            'analysis_type' => $analysisType,
            'result' => 'mock_analysis',
            'confidence' => 0.9,
            'timestamp' => date('c')
        ];

        // Tambahkan field khusus berdasarkan analysis type
        switch ($analysisType) {
            case 'stock_optimization':
                $baseResult['optimizations'] = [
                    'mock_item' => [
                        'current_stock' => 10,
                        'optimal_stock' => 15,
                        'safety_stock' => 5,
                        'reorder_point' => 8
                    ]
                ];
                break;
            case 'sales_trends':
                $baseResult['trend'] = 'increasing';
                $baseResult['growth_rate'] = 0.1;
                break;
            case 'inventory_turnover':
                $baseResult['predictions'] = ['item1' => 30];
                break;
            case 'purchase_recommendations':
                $baseResult['recommendations'] = ['supplier1'];
                break;
            case 'safety_stock':
                $baseResult['safety_stock'] = 5;
                break;
        }

        return $baseResult;
    }

    public function generate(array $data, string $reportType = 'summary'): array
    {
        return [
            'report_type' => $reportType,
            'content' => 'mock_report',
            'timestamp' => date('c')
        ];
    }

    public function isAvailable(): bool
    {
        return true;
    }
}

class AIServiceAdvancedTest extends TestCase
{
    private AIService $aiService;
    private Logger $logger;
    private MockAIStrategy $mockStrategy;

    protected function setUp(): void
    {
        // Create mock logger
        $this->logger = $this->createMock(Logger::class);
        
        // Configure logger mock
        $this->logger->method('info')->willReturnCallback(function ($message, $context = []) {});
        $this->logger->method('warning')->willReturnCallback(function ($message, $context = []) {});
        $this->logger->method('error')->willReturnCallback(function ($message, $context = []) {});
        $this->logger->method('debug')->willReturnCallback(function ($message, $context = []) {});
        
        // Create AIService instance
        $this->aiService = new AIService($this->logger, true);
        
        // Register mock strategy
        $this->mockStrategy = new MockAIStrategy();
        $this->aiService->registerStrategy('mock', $this->mockStrategy);
    }

    public function testSalesTrendsAnalysis(): void
    {
        $salesData = [
            [
                'date' => '2024-01-01',
                'quantity' => 10,
                'revenue' => 1000.00
            ],
            [
                'date' => '2024-01-02',
                'quantity' => 15,
                'revenue' => 1500.00
            ]
        ];

        $result = $this->aiService->analyzeSalesTrends($salesData, 30);

        $this->assertArrayHasKey('analysis_type', $result);
        $this->assertEquals('sales_trends', $result['analysis_type']);
        $this->assertArrayHasKey('trend', $result);
    }

    public function testInventoryTurnoverPrediction(): void
    {
        $items = [
            [
                'name' => 'Laptop Dell XPS 13',
                'currentStock' => 25,
                'salesHistory' => [
                    ['date' => '2024-01-01', 'quantity' => 5],
                    ['date' => '2024-01-02', 'quantity' => 3]
                ]
            ]
        ];

        $result = $this->aiService->predictInventoryTurnover($items);

        $this->assertArrayHasKey('analysis_type', $result);
        $this->assertEquals('inventory_turnover', $result['analysis_type']);
        $this->assertArrayHasKey('predictions', $result);
    }

    public function testStockOptimization(): void
    {
        $inventoryData = [
            [
                'name' => 'Laptop Dell XPS 13',
                'currentStock' => 15,
                'minStock' => 5,
                'maxStock' => 50,
                'leadTimeDays' => 7,
                'unitCost' => 1000.00,
                'dailyUsage' => 2
            ]
        ];

        $result = $this->aiService->optimizeStockLevels($inventoryData);

        $this->assertArrayHasKey('analysis_type', $result);
        $this->assertEquals('stock_optimization', $result['analysis_type']);
        $this->assertArrayHasKey('optimizations', $result); // Pastikan key ini ada
        $this->assertIsArray($result['optimizations']);
    }

    public function testPurchaseRecommendations(): void
    {
        $supplierData = [
            [
                'name' => 'Supplier A',
                'leadTimeDays' => 7,
                'reliabilityScore' => 0.9,
                'costScore' => 0.8
            ]
        ];

        $result = $this->aiService->generatePurchaseRecommendations($supplierData);

        $this->assertArrayHasKey('analysis_type', $result);
        $this->assertEquals('purchase_recommendations', $result['analysis_type']);
    }

    public function testSafetyStockCalculation(): void
    {
        $itemHistory = [
            [
                'date' => '2024-01-01',
                'demand' => 10,
                'leadTime' => 7
            ],
            [
                'date' => '2024-01-02',
                'demand' => 15,
                'leadTime' => 7
            ]
        ];

        $result = $this->aiService->calculateSafetyStock($itemHistory);

        $this->assertArrayHasKey('analysis_type', $result);
        $this->assertEquals('safety_stock', $result['analysis_type']);
    }

    public function testEmptyDataValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sales data cannot be empty');
        
        // PERBAIKAN: Pastikan ini benar-benar empty array
        $this->aiService->analyzeSalesTrends([], 30);
    }

    public function testInvalidDataStructure(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sales data structure');
        
        // PERBAIKAN: Data yang benar-benar invalid
        $invalidData = [
            ['invalid_field' => 'data'] // Tidak ada field yang required
        ];

        $this->aiService->analyzeSalesTrends($invalidData, 30);
    }

    public function testServiceAvailability(): void
    {
        $this->assertTrue($this->aiService->isAvailable());
        
        $disabledService = new AIService($this->logger, false);
        $this->assertFalse($disabledService->isAvailable());
    }

    public function testFallbackMechanism(): void
    {
        $unavailableStrategy = new class implements AIStrategy {
            public function analyze(array $data, string $analysisType = 'stock_prediction'): array { 
                throw new \RuntimeException('Strategy not available');
            }
            public function generate(array $data, string $reportType = 'summary'): array { return []; }
            public function isAvailable(): bool { return false; }
        };
        
        $service = new AIService($this->logger, true);
        $service->registerStrategy('unavailable', $unavailableStrategy);
        
        $salesData = [
            ['date' => '2024-01-01', 'quantity' => 10, 'revenue' => 1000]
        ];
        
        $result = $service->analyzeSalesTrends($salesData, 30);
        
        $this->assertArrayHasKey('is_fallback', $result);
        $this->assertTrue($result['is_fallback']);
    }

    public function testStrategyRegistration(): void
    {
        $strategies = $this->aiService->getAvailableStrategies();
        $this->assertContains('mock', $strategies);
        
        $this->assertTrue($this->aiService->setStrategy('mock'));
        $this->assertFalse($this->aiService->setStrategy('nonexistent'));
    }

    // TEST TAMBAHAN: Validasi data yang benar-benar kosong
    public function testTrulyEmptyData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->aiService->analyzeSalesTrends([], 30);
    }

    // TEST TAMBAHAN: Validasi data dengan struktur yang sangat invalid
    public function testVeryInvalidData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        
        $veryInvalidData = [
            'not_an_array' // Bukan array asosiatif
        ];
        
        $this->aiService->analyzeSalesTrends($veryInvalidData, 30);
    }
}
?>