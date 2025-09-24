<?php
declare(strict_types=1);

namespace Tests\Unit\Controller;

use App\Controller\AIAnalysisController;
use App\Service\AIService;
use App\Service\InventoryAnalysisService;
use App\Utility\Logger;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class AIAnalysisControllerTest extends TestCase
{
    private AIService $mockAIService;
    private InventoryAnalysisService $mockAnalysisService;
    private Logger $mockLogger;
    private AIAnalysisController $controller;

    protected function setUp(): void
    {
        // kosongkan superglobals
        $_GET = [];
        $_POST = [];

        $this->mockAIService = $this->createMock(AIService::class);
        $this->mockAnalysisService = $this->createMock(InventoryAnalysisService::class);
        $this->mockLogger = $this->createMock(Logger::class);

        // controller akan diinisialisasi ulang dalam tiap test
    }

    public function testGetComprehensiveAnalysisSuccess(): void
    {
        $mockAnalysis = [
            'summary' => ['total_items' => 100],
            'risk_assessment' => 'low',
            'ai_insights' => ['All systems optimal'],
            'items_analyzed' => 100
        ];

        $this->mockAnalysisService->method('getComprehensiveAnalysis')
            ->willReturn($mockAnalysis);

        $this->mockAIService->method('isAvailable')
            ->willReturn(true);

        $this->controller = new AIAnalysisController(
            $this->mockAnalysisService,
            $this->mockAIService,
            $this->mockLogger
        );
        $this->controller->enableTestMode();

        $reflectionMethod = new ReflectionMethod($this->controller, 'getComprehensiveAnalysis');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invoke($this->controller);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('analysis', $result['data']);
        $this->assertArrayHasKey('ai_service_available', $result['data']);
    }

    public function testGetAIStatus(): void
    {
        $this->mockAIService->method('isAvailable')
            ->willReturn(true);
        $this->mockAIService->method('getAvailableStrategies')
            ->willReturn(['ollama', 'advanced']);

        $this->controller = new AIAnalysisController(
            $this->mockAnalysisService,
            $this->mockAIService,
            $this->mockLogger
        );
        $this->controller->enableTestMode();

        $reflectionMethod = new ReflectionMethod($this->controller, 'getAIStatus');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invoke($this->controller);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('ai_service_available', $result['data']);
        $this->assertTrue($result['data']['ai_service_available']);
    }

    public function testPredictInventoryNeedsValidation(): void
    {
        $_GET['forecast_days'] = '7';

        $this->mockAnalysisService->method('predictInventoryNeeds')
            ->willReturn(['confidence_score' => 0.8]);

        $this->controller = new AIAnalysisController(
            $this->mockAnalysisService,
            $this->mockAIService,
            $this->mockLogger
        );
        $this->controller->enableTestMode();

        $reflectionMethod = new ReflectionMethod($this->controller, 'predictInventoryNeeds');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invoke($this->controller);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('prediction', $result['data']);
        $this->assertEquals(7, $result['data']['forecast_period']);
    }

    public function testAnalyzeSalesTrendsWithValidData(): void
    {
        $mockSalesData = [
            ['date' => '2024-01-01', 'quantity' => 10, 'revenue' => 100],
            ['date' => '2024-01-02', 'quantity' => 15, 'revenue' => 150]
        ];

        $_POST = [
            'sales_data' => $mockSalesData,
            'period_days' => 30
        ];

        $this->mockAIService->method('analyzeSalesTrends')
            ->willReturn(['trend' => 'increasing']);

        $this->controller = new AIAnalysisController(
            $this->mockAnalysisService,
            $this->mockAIService,
            $this->mockLogger
        );
        $this->controller->enableTestMode();

        $reflectionMethod = new ReflectionMethod($this->controller, 'analyzeSalesTrends');
        $reflectionMethod->setAccessible(true);

        $result = $reflectionMethod->invoke($this->controller);

        $this->assertIsArray($result);
        $this->assertEquals('success', $result['status']);
        $this->assertArrayHasKey('analysis', $result['data']);
        $this->assertEquals(30, $result['data']['period_analyzed']);
    }

    protected function tearDown(): void
    {
        $_GET = [];
        $_POST = [];
    }
}
