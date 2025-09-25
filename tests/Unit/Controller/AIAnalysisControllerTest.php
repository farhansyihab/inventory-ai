<?php
// File: tests/Unit/Controller/AIAnalysisControllerTest.php (FIXED)
declare(strict_types=1);

namespace Tests\Unit\Controller;

use App\Controller\AIAnalysisController;
use App\Service\InventoryAnalysisService;
use App\Service\AIService;
use App\Utility\Logger;
use PHPUnit\Framework\TestCase;

class AIAnalysisControllerTest extends TestCase
{
    private AIService $aiService;
    private InventoryAnalysisService $analysisService;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger(__DIR__ . '/../../logs/controller_test.log');
        $this->aiService = $this->createMock(AIService::class);
        $this->analysisService = $this->createMock(InventoryAnalysisService::class);
    }

    public function testAnalyzeSalesTrendsWithValidData(): void
    {
        // Setup POST data SEBELUM membuat controller
        $_POST = [
            'sales_data' => [
                [
                    'date' => '2024-01-01',
                    'quantity' => 10,
                    'revenue' => 100.0,
                    'item_id' => 'test_001',
                    'item_name' => 'Test Product'
                ]
            ],
            'period_days' => 30
        ];

        $mockAnalysisResult = [
            'trend_direction' => 'increasing',
            'growth_rate' => 0.15,
            'confidence' => 0.85,
            'period_analyzed' => 30, // ✅ TAMBAHKAN INI
            'data_points' => 1
        ];

        $this->aiService
            ->method('analyzeSalesTrends')
            ->willReturn($mockAnalysisResult);

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();

        $response = $controller->analyzeSalesTrends();

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('analysis', $response['data']);
        $this->assertEquals('increasing', $response['data']['analysis']['trend_direction']);
    }

    public function testAnalyzeSalesTrendsWithMissingData(): void
    {
        // Setup empty POST data
        $_POST = [];

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();

        $response = $controller->analyzeSalesTrends();

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
        $this->assertArrayHasKey('errors', $response);
    }

    public function testGetComprehensiveAnalysis(): void
    {
        $mockAnalysisResult = [
            'summary' => ['totalItems' => 100, 'totalValue' => 50000],
            'risk_assessment' => 'medium',
            'ai_insights' => ['Optimize stock levels'],
            'items_analyzed' => 100
        ];

        $this->analysisService
            ->method('getComprehensiveAnalysis')
            ->willReturn($mockAnalysisResult);

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();

        $response = $controller->getComprehensiveAnalysis();

        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('success', $response['status']);
        $this->assertEquals(100, $response['data']['analysis']['items_analyzed']);
    }

    public function testMonitorCriticalItems(): void
    {
        $mockMonitoring = [
            'alerts' => [
                ['type' => 'low_stock', 'item_name' => 'Test Product', 'urgency' => 'high']
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
        $this->assertCount(1, $response['data']['monitoring']['alerts']);
    }

    public function testPredictInventoryNeeds(): void
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
        $this->assertEquals(30, $response['data']['prediction']['forecast_period']);
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

    protected function tearDown(): void
    {
        // Clean up global state
        $_POST = [];
        $_GET = [];
    }
}