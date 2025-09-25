<?php
// File: tests/Unit/AI/AIServiceAdvancedTest.php
declare(strict_types=1);

namespace Tests\Unit\AI;

use App\Service\AIService;
use App\Service\AIStrategy;
use App\Utility\Logger;
use App\Utility\PerformanceBenchmark;
use PHPUnit\Framework\TestCase;

class AIServiceAdvancedTest extends TestCase
{
    private AIService $aiService;
    private $loggerMock;
    private $strategyMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->strategyMock = $this->createMock(AIStrategy::class);
        
        $this->aiService = new AIService($this->loggerMock, true);
        $this->aiService->registerStrategy('advanced_strategy', $this->strategyMock);

        PerformanceBenchmark::enable();
        PerformanceBenchmark::clear();
    }

    protected function tearDown(): void
    {
        PerformanceBenchmark::clear();
    }

    public function testAnalyzeSalesTrendsPerformance(): void
    {
        $salesData = $this->generateSalesData(1000); // 1000 data points
        $periodDays = 30;

        $expectedResult = [
            'trend_direction' => 'increasing',
            'growth_rate' => 0.15,
            'confidence' => 0.85
        ];

        $this->strategyMock
            ->method('analyze')
            ->willReturn($expectedResult);

        // Measure performance
        $result = PerformanceBenchmark::measure(
            fn() => $this->aiService->analyzeSalesTrends($salesData, $periodDays),
            'analyze_sales_trends_1000_points'
        );

        // Assert performance thresholds
        $this->assertTrue(
            PerformanceBenchmark::meetsThreshold('analyze_sales_trends_1000_points', 2.0, 50 * 1024 * 1024),
            'Sales trends analysis should complete within 2 seconds and 50MB memory'
        );

        $this->assertEquals($expectedResult['trend_direction'], $result['trend_direction']);
    }

    public function testPredictInventoryTurnoverWithLargeDataset(): void
    {
        $items = $this->generateInventoryItems(500); // 500 items

        $expectedResult = [
            'turnover_analysis' => [],
            'overall_metrics' => ['average_turnover' => 2.1],
            'efficiency_score' => 0.75
        ];

        $this->strategyMock
            ->method('analyze')
            ->willReturn($expectedResult);

        $result = PerformanceBenchmark::measure(
            fn() => $this->aiService->predictInventoryTurnover($items),
            'predict_turnover_500_items'
        );

        // Performance assertion
        $this->assertTrue(
            PerformanceBenchmark::meetsThreshold('predict_turnover_500_items', 3.0, 75 * 1024 * 1024),
            'Turnover prediction should complete within 3 seconds for 500 items'
        );

        $this->assertArrayHasKey('turnover_analysis', $result);
    }

    public function testOptimizeStockLevelsPerformance(): void
    {
        $inventoryData = $this->generateOptimizationData(200); // 200 items

        $expectedResult = [
            'optimizations' => [],
            'total_potential_savings' => 15000.50
        ];

        $this->strategyMock
            ->method('analyze')
            ->willReturn($expectedResult);

        $result = PerformanceBenchmark::measure(
            fn() => $this->aiService->optimizeStockLevels($inventoryData),
            'optimize_stock_200_items'
        );

        $this->assertTrue(
            PerformanceBenchmark::meetsThreshold('optimize_stock_200_items', 2.5, 60 * 1024 * 1024)
        );

        $this->assertArrayHasKey('total_potential_savings', $result);
    }

    public function testErrorHandlingInSalesTrendsAnalysis(): void
    {
        $invalidSalesData = [['invalid' => 'data']]; // Missing required fields

        $this->expectException(\InvalidArgumentException::class);
        
        $this->aiService->analyzeSalesTrends($invalidSalesData, 30);
    }

    public function testFallbackMechanismWhenStrategyFails(): void
    {
        $salesData = $this->generateSalesData(100);

        $this->strategyMock
            ->method('analyze')
            ->willThrowException(new \RuntimeException('Strategy unavailable'));

        $this->loggerMock
            ->expects($this->once())
            ->method('warning');

        $result = $this->aiService->analyzeSalesTrends($salesData, 30);

        $this->assertArrayHasKey('is_fallback', $result);
        $this->assertTrue($result['is_fallback']);
        $this->assertArrayHasKey('confidence', $result);
    }

    public function testMemoryUsageWithVeryLargeDataset(): void
    {
        $largeDataset = $this->generateSalesData(10000); // 10,000 data points

        // Skip test jika memory insufficient
        if (memory_get_usage(true) > (100 * 1024 * 1024)) {
            $this->markTestSkipped('Insufficient memory for large dataset test');
        }

        $this->strategyMock
            ->method('analyze')
            ->willReturn(['status' => 'analyzed']);

        $result = PerformanceBenchmark::measure(
            fn() => $this->aiService->analyzeSalesTrends($largeDataset, 30),
            'analyze_sales_trends_10000_points'
        );

        $report = PerformanceBenchmark::generateReport();
        $benchmark = end($report['benchmarks']);

        $this->assertLessThan(
            100 * 1024 * 1024, // 100MB
            $benchmark['memory_used'],
            'Memory usage should be under 100MB for 10,000 data points'
        );
    }

    public function testBatchProcessingPerformance(): void
    {
        $operations = [
            'sales_trends' => fn() => $this->aiService->analyzeSalesTrends($this->generateSalesData(500), 30),
            'turnover_prediction' => fn() => $this->aiService->predictInventoryTurnover($this->generateInventoryItems(300)),
            'stock_optimization' => fn() => $this->aiService->optimizeStockLevels($this->generateOptimizationData(150))
        ];

        $this->strategyMock
            ->method('analyze')
            ->willReturn(['status' => 'success']);

        $results = PerformanceBenchmark::measureBatch($operations, false);

        $this->assertCount(3, $results);
        $this->assertArrayHasKey('sales_trends', $results);
        $this->assertArrayHasKey('turnover_prediction', $results);
        $this->assertArrayHasKey('stock_optimization', $results);
    }

    private function generateSalesData(int $count): array
    {
        $data = [];
        $baseDate = new \DateTime('-30 days');
        
        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'date' => (clone $baseDate)->modify("+$i days")->format('Y-m-d'),
                'quantity' => rand(1, 100),
                'revenue' => rand(100, 5000) / 100,
                'item_id' => 'item_' . $i,
                'item_name' => 'Product ' . $i
            ];
        }
        
        return $data;
    }

    private function generateInventoryItems(int $count): array
    {
        $items = [];
        
        for ($i = 0; $i < $count; $i++) {
            $items[] = [
                'name' => 'Product ' . $i,
                'currentStock' => rand(0, 100),
                'salesHistory' => array_map(fn() => ['quantity' => rand(1, 20)], range(1, 30)),
                'price' => rand(100, 1000) / 100,
                'minStockLevel' => rand(5, 20)
            ];
        }
        
        return $items;
    }

    private function generateOptimizationData(int $count): array
    {
        $data = [];
        
        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'name' => 'Product ' . $i,
                'currentStock' => rand(0, 200),
                'minStock' => rand(5, 20),
                'maxStock' => rand(100, 500),
                'leadTimeDays' => rand(1, 14),
                'unitCost' => rand(10, 100) / 100,
                'dailyUsage' => rand(1, 10)
            ];
        }
        
        return $data;
    }
}