<?php
// File: tests/Integration/AI/InventoryAnalysisIntegrationTest.php
declare(strict_types=1);

namespace Tests\Integration\AI;

use App\Service\InventoryAnalysisService;
use App\Service\AIService;
use App\Service\InventoryService;
use App\Repository\InventoryRepository;
use App\Utility\Logger;
use MongoDB\Client;
use PHPUnit\Framework\TestCase;

class InventoryAnalysisIntegrationTest extends TestCase
{
    private InventoryAnalysisService $analysisService;
    private AIService $aiService;
    private InventoryService $inventoryService;
    private InventoryRepository $inventoryRepo;
    private Logger $logger;
    private Client $mongoClient;

    protected function setUp(): void
    {
        // Setup MongoDB connection
        $this->mongoClient = new Client('mongodb://localhost:27017');
        $this->mongoClient->selectDatabase('test_inventory_ai')->drop();
        
        // Initialize services dengan real dependencies
        $this->logger = new Logger(__DIR__ . '/../../logs/integration_test.log');
        $this->inventoryRepo = new InventoryRepository($this->logger);
        $this->inventoryService = new InventoryService($this->inventoryRepo, $this->logger);
        $this->aiService = new AIService($this->logger, true);
        $this->analysisService = new InventoryAnalysisService(
            $this->aiService,
            $this->inventoryService,
            $this->logger
        );

        // Create test data
        $this->createTestInventoryData();
    }

    protected function tearDown(): void
    {
        // Cleanup
        $this->mongoClient->selectDatabase('test_inventory_ai')->drop();
    }

    private function createTestInventoryData(): void
    {
        $testItems = [
            [
                'name' => 'Laptop Dell XPS 13',
                'description' => 'High-performance business laptop',
                'quantity' => 15,
                'price' => 1299.99,
                'minStockLevel' => 5,
                'categoryId' => 'electronics',
                'supplierId' => 'supplier_001',
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime()
            ],
            [
                'name' => 'Wireless Mouse Logitech',
                'description' => 'Ergonomic wireless mouse',
                'quantity' => 3, // Low stock
                'price' => 29.99,
                'minStockLevel' => 10,
                'categoryId' => 'accessories',
                'supplierId' => 'supplier_002',
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime()
            ],
            [
                'name' => 'Mechanical Keyboard',
                'description' => 'RGB mechanical keyboard',
                'quantity' => 0, // Out of stock
                'price' => 89.99,
                'minStockLevel' => 5,
                'categoryId' => 'accessories',
                'supplierId' => 'supplier_003',
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime()
            ],
            [
                'name' => '27-inch Monitor',
                'description' => '4K UHD monitor',
                'quantity' => 25,
                'price' => 399.99,
                'minStockLevel' => 8,
                'categoryId' => 'electronics',
                'supplierId' => 'supplier_001',
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime()
            ]
        ];

        foreach ($testItems as $itemData) {
            $this->inventoryRepo->create($itemData);
        }
    }

    public function testComprehensiveAnalysisIntegration(): void
    {
        $result = $this->analysisService->getComprehensiveAnalysis();

        // Assert structure
        $this->assertArrayHasKey('summary', $result);
        $this->assertArrayHasKey('risk_assessment', $result);
        $this->assertArrayHasKey('ai_insights', $result);
        $this->assertArrayHasKey('critical_items', $result);

        // Assert data integrity
        $this->assertIsArray($result['summary']);
        $this->assertIsArray($result['ai_insights']);
        $this->assertIsArray($result['critical_items']['low_stock']);
        $this->assertIsArray($result['critical_items']['out_of_stock']);

        // Assert business logic
        $this->assertContains($result['risk_assessment'], ['low', 'medium', 'high', 'critical', 'unknown']);
        $this->assertGreaterThan(0, $result['items_analyzed']);

        $this->logger->info('Comprehensive analysis integration test passed', [
            'items_analyzed' => $result['items_analyzed'],
            'risk_level' => $result['risk_assessment']
        ]);
    }

    public function testWeeklyReportGenerationIntegration(): void
    {
        $report = $this->analysisService->generateWeeklyReport();

        // Assert report structure
        $this->assertArrayHasKey('period', $report);
        $this->assertArrayHasKey('executive_summary', $report);
        $this->assertArrayHasKey('key_metrics', $report);
        $this->assertArrayHasKey('action_items', $report);

        // Assert period data
        $this->assertEquals('weekly', $report['period']['type']);
        $this->assertArrayHasKey('start', $report['period']);
        $this->assertArrayHasKey('end', $report['period']);

        // Assert metrics
        $this->assertArrayHasKey('total_inventory_value', $report['key_metrics']);
        $this->assertIsFloat($report['key_metrics']['total_inventory_value']);

        $this->logger->info('Weekly report integration test passed');
    }

    public function testCriticalItemsMonitoringIntegration(): void
    {
        $monitoringResult = $this->analysisService->monitorCriticalItems();

        // Assert structure
        $this->assertArrayHasKey('alerts', $monitoringResult);
        $this->assertArrayHasKey('risk_level', $monitoringResult);
        $this->assertArrayHasKey('summary', $monitoringResult);

        // Assert alerts contain expected items
        $this->assertGreaterThan(0, count($monitoringResult['alerts']));
        
        $alertTypes = array_column($monitoringResult['alerts'], 'type');
        $this->assertContains('low_stock', $alertTypes);
        $this->assertContains('out_of_stock', $alertTypes);

        // Assert summary data
        $this->assertGreaterThanOrEqual(1, $monitoringResult['summary']['low_stock_count']);
        $this->assertGreaterThanOrEqual(1, $monitoringResult['summary']['out_of_stock_count']);

        $this->logger->info('Critical items monitoring integration test passed', [
            'total_alerts' => count($monitoringResult['alerts'])
        ]);
    }

    public function testInventoryNeedsPredictionIntegration(): void
    {
        $prediction = $this->analysisService->predictInventoryNeeds(30);

        // Assert structure
        $this->assertArrayHasKey('forecast_period', $prediction);
        $this->assertArrayHasKey('prediction_summary', $prediction);
        $this->assertArrayHasKey('sales_trends', $prediction);
        $this->assertArrayHasKey('confidence_score', $prediction);

        // Assert forecast period
        $this->assertEquals(30, $prediction['forecast_period']);

        // Assert confidence score
        $this->assertGreaterThanOrEqual(0, $prediction['confidence_score']);
        $this->assertLessThanOrEqual(1, $prediction['confidence_score']);

        $this->logger->info('Inventory needs prediction integration test passed', [
            'forecast_days' => $prediction['forecast_period'],
            'confidence' => $prediction['confidence_score']
        ]);
    }

    public function testInventoryOptimizationIntegration(): void
    {
        $optimizationResult = $this->analysisService->optimizeInventory();

        // Assert structure
        $this->assertArrayHasKey('optimization_results', $optimizationResult);
        $this->assertArrayHasKey('savings_analysis', $optimizationResult);
        $this->assertArrayHasKey('implementation_plan', $optimizationResult);

        // Assert optimization data
        $this->assertIsArray($optimizationResult['optimization_results']);
        $this->assertArrayHasKey('total_potential_savings', $optimizationResult['savings_analysis']);

        // Assert savings is numeric
        $this->assertIsNumeric($optimizationResult['savings_analysis']['total_potential_savings']);

        $this->logger->info('Inventory optimization integration test passed', [
            'potential_savings' => $optimizationResult['savings_analysis']['total_potential_savings']
        ]);
    }

    public function testPerformanceWithLargeDataset(): void
    {
        // Create larger dataset untuk performance testing
        $this->createLargeTestDataset(100); // 100 items

        $startTime = microtime(true);
        
        $result = $this->analysisService->getComprehensiveAnalysis();
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Assert performance requirements (max 5 seconds untuk 100 items)
        $this->assertLessThan(5.0, $executionTime, 
            "Analysis should complete within 5 seconds for 100 items. Took: {$executionTime}s"
        );

        // Assert memory usage (max 100MB)
        $memoryUsage = memory_get_peak_usage(true) / 1024 / 1024; // Convert to MB
        $this->assertLessThan(100, $memoryUsage,
            "Memory usage should be under 100MB. Used: {$memoryUsage}MB"
        );

        $this->logger->info('Performance test passed', [
            'execution_time' => round($executionTime, 2) . 's',
            'memory_usage' => round($memoryUsage, 2) . 'MB',
            'items_analyzed' => $result['items_analyzed']
        ]);
    }

    private function createLargeTestDataset(int $itemCount): void
    {
        $categories = ['electronics', 'accessories', 'furniture', 'office_supplies'];
        $suppliers = ['supplier_001', 'supplier_002', 'supplier_003', 'supplier_004'];

        for ($i = 1; $i <= $itemCount; $i++) {
            $itemData = [
                'name' => "Test Item {$i}",
                'description' => "Description for test item {$i}",
                'quantity' => rand(0, 50),
                'price' => round(rand(100, 5000) / 100, 2), // Random price between 1.00 and 50.00
                'minStockLevel' => rand(5, 20),
                'categoryId' => $categories[array_rand($categories)],
                'supplierId' => $suppliers[array_rand($suppliers)],
                'createdAt' => new \DateTime(),
                'updatedAt' => new \DateTime()
            ];

            $this->inventoryRepo->create($itemData);
        }
    }

    public function testErrorHandlingAndFallbacks(): void
    {
        // Test dengan AI service disabled
        $disabledAIService = new AIService($this->logger, false);
        $fallbackAnalysisService = new InventoryAnalysisService(
            $disabledAIService,
            $this->inventoryService,
            $this->logger
        );

        $result = $fallbackAnalysisService->getComprehensiveAnalysis();

        // Assert fallback mechanism bekerja
        $this->assertArrayHasKey('is_fallback', $result);
        $this->assertTrue($result['is_fallback']);
        $this->assertArrayHasKey('summary', $result);

        $this->logger->info('Error handling and fallback test passed');
    }

    public function testDataConsistencyAcrossAnalyses(): void
    {
        // Run multiple analyses dan assert consistency
        $analysis1 = $this->analysisService->getComprehensiveAnalysis();
        $analysis2 = $this->analysisService->getComprehensiveAnalysis();

        // Assert consistent structure
        $this->assertEquals(
            array_keys($analysis1),
            array_keys($analysis2),
            'Analysis structure should be consistent across runs'
        );

        // Assert consistent item count
        $this->assertEquals(
            $analysis1['items_analyzed'],
            $analysis2['items_analyzed'],
            'Items analyzed should be consistent'
        );

        $this->logger->info('Data consistency test passed');
    }
}