<?php
// File: tests/Functional/Api/AIAnalysisEndpointsTest.php (FIXED VERSION)
declare(strict_types=1);

namespace Tests\Functional\Api;

use App\Controller\AIAnalysisController;
use App\Service\InventoryAnalysisService;
use App\Service\AIService;
use App\Utility\Logger;
use PHPUnit\Framework\TestCase;

class AIAnalysisEndpointsTest extends TestCase
{
    private AIService $aiService;
    private InventoryAnalysisService $analysisService;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger(__DIR__ . '/../../logs/api_test.log');
        $this->aiService = $this->createMock(AIService::class);
        $this->analysisService = $this->createMock(InventoryAnalysisService::class);
    }

    public function testComprehensiveAnalysisEndpoint(): void
    {
        $mockAnalysisResult = [
            'summary' => ['totalItems' => 150, 'totalValue' => 50000],
            'risk_assessment' => 'medium',
            'ai_insights' => ['Optimize stock levels for seasonal items'],
            'items_analyzed' => 150
        ];

        $this->analysisService
            ->method('getComprehensiveAnalysis')
            ->willReturn($mockAnalysisResult);

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();
        
        $response = $controller->getComprehensiveAnalysis();

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals(150, $response['data']['analysis']['items_analyzed']);
    }

    public function testWeeklyReportEndpoint(): void
    {
        $mockReport = [
            'period' => ['type' => 'weekly', 'start' => '2024-01-01', 'end' => '2024-01-07'],
            'executive_summary' => ['Weekly performance is stable'],
            'key_metrics' => ['total_inventory_value' => 75000.50]
        ];

        $this->analysisService
            ->method('generateWeeklyReport')
            ->willReturn($mockReport);

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();
        
        $response = $controller->generateWeeklyReport();

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('report', $response['data']);
    }

    public function testCriticalItemsMonitoringEndpoint(): void
    {
        $mockMonitoring = [
            'alerts' => [
                [
                    'type' => 'low_stock',
                    'item_name' => 'Test Product',
                    'urgency' => 'high'
                ]
            ],
            'risk_level' => 'medium',
            'total_critical_items' => 1
        ];

        $this->analysisService
            ->method('monitorCriticalItems')
            ->willReturn($mockMonitoring);

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();
        
        $response = $controller->monitorCriticalItems();

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('monitoring', $response['data']);
    }

    public function testInventoryPredictionEndpoint(): void
    {
        $mockPrediction = [
            'forecast_period' => 30,
            'prediction_summary' => ['Increased demand expected'],
            'confidence_score' => 0.85
        ];

        $this->analysisService
            ->method('predictInventoryNeeds')
            ->with(30)
            ->willReturn($mockPrediction);

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();
        
        $response = $controller->predictInventoryNeeds(30);

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals(30, $response['data']['prediction']['forecast_period']);
    }

    public function testSalesTrendsAnalysisEndpoint(): void
    {
        $validSalesData = $this->generateValidSalesDataForTest();
        $periodDays = 30;
        
        $_POST = [
            'sales_data' => $validSalesData,
            'period_days' => $periodDays
        ];

        $mockAnalysis = [
            'trend_direction' => 'increasing',
            'growth_rate' => 0.15,
            'confidence' => 0.9,
            'period_analyzed' => $periodDays,
            'data_points' => count($validSalesData)
        ];

        $this->aiService
            ->method('analyzeSalesTrends')
            ->willReturn($mockAnalysis);

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();

        $response = $controller->analyzeSalesTrends();

        // Verifikasi dengan assert response, bukan internal state
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals('increasing', $response['data']['analysis']['trend_direction']);
        
        // Hapus debug code yang mengakses internal state
        // echo "\n=== DEBUG REQUEST DATA ===\n";
        // var_export($controller->getRequestData()); // ❌ Jangan lakukan ini
    }


    public function testSalesTrendsAnalysisWithMissingData(): void
    {
        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();

        // Test with missing sales_data
        $_POST = ['period_days' => 30];

        $response = $controller->analyzeSalesTrends();

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testAIStatusEndpoint(): void
    {
        $this->aiService
            ->method('isAvailable')
            ->willReturn(true);

        $this->aiService
            ->method('getAvailableStrategies')
            ->willReturn(['advanced_analysis', 'ollama']);

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();
        
        $response = $controller->getAIStatus();

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
        $this->assertTrue($response['data']['ai_service_available']);
        $this->assertContains('advanced_analysis', $response['data']['available_strategies']);
    }

    public function testOptimizeInventoryEndpoint(): void
    {
        $mockOptimization = [
            'optimization_results' => [],
            'savings_analysis' => ['total_potential_savings' => 15000.50],
            'total_items_optimized' => 50
        ];

        $this->analysisService
            ->method('optimizeInventory')
            ->willReturn($mockOptimization);

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();
        
        $response = $controller->optimizeInventory();

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals(15000.50, $response['data']['optimization']['savings_analysis']['total_potential_savings']);
    }

    public function testEndpointPerformance(): void
    {
        $mockResult = [
            'summary' => ['status' => 'basic_analysis'],
            'risk_assessment' => 'unknown',
            'items_analyzed' => 1
        ];

        $this->analysisService
            ->method('getComprehensiveAnalysis')
            ->willReturn($mockResult);

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();

        $startTime = microtime(true);
        $response = $controller->getComprehensiveAnalysis();
        $endTime = microtime(true);

        $responseTime = $endTime - $startTime;

        $this->assertLessThan(2.0, $responseTime, 
            "Endpoint response time should be under 2 seconds. Actual: {$responseTime}s"
        );

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
    }

    public function testErrorHandling(): void
    {
        $this->analysisService
            ->method('getComprehensiveAnalysis')
            ->willThrowException(new \RuntimeException('Service unavailable'));

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();
        
        $response = $controller->getComprehensiveAnalysis();

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
        $this->assertStringContainsString('Failed', $response['message']);
    }

    /**
     * FIX: Generate valid sales data structure that matches what AIService expects
     */
    private function generateValidSalesDataForTest(): array
    {
        return [
            [
                'date' => '2024-01-01',
                'quantity' => 10,
                'revenue' => 100.50,
                'item_id' => 'item_001',
                'item_name' => 'Test Product 1'
            ],
            [
                'date' => '2024-01-02',
                'quantity' => 15,
                'revenue' => 150.75,
                'item_id' => 'item_001',
                'item_name' => 'Test Product 1'
            ],
            [
                'date' => '2024-01-03',
                'quantity' => 8,
                'revenue' => 80.25,
                'item_id' => 'item_001',
                'item_name' => 'Test Product 1'
            ],
            [
                'date' => '2024-01-04',
                'quantity' => 20,
                'revenue' => 200.00,
                'item_id' => 'item_001',
                'item_name' => 'Test Product 1'
            ],
            [
                'date' => '2024-01-05',
                'quantity' => 12,
                'revenue' => 120.60,
                'item_id' => 'item_001',
                'item_name' => 'Test Product 1'
            ]
        ];
    }

    protected function tearDown(): void
    {
        // Clean up global state
        $_POST = [];
        $_GET = [];
    }
}