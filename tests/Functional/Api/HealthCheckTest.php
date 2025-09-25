<?php
// File: tests/Functional/Api/HealthCheckTest.php (FIXED)
declare(strict_types=1);

namespace Tests\Functional\Api;

use App\Controller\AIAnalysisController;
use App\Service\InventoryAnalysisService;
use App\Service\AIService;
use App\Utility\Logger;
use PHPUnit\Framework\TestCase;

class HealthCheckTest extends TestCase
{
    private AIService $aiService;
    private InventoryAnalysisService $analysisService;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger(__DIR__ . '/../../logs/health_check_test.log');
        $this->aiService = $this->createMock(AIService::class);
        $this->analysisService = $this->createMock(InventoryAnalysisService::class);
    }

    public function testApiHealthEndpoint(): void
    {
        // Mock AI service sebagai available
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

    public function testBasicEndpointResponse(): void
    {
        $mockAnalysisResult = [
            'summary' => ['status' => 'basic_analysis'],
            'risk_assessment' => 'unknown',
            'items_analyzed' => 0
        ];

        $this->analysisService
            ->method('getComprehensiveAnalysis')
            ->willReturn($mockAnalysisResult);

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();

        $response = $controller->getComprehensiveAnalysis();

        // Basic health check: endpoint should return valid response structure
        $this->assertArrayHasKey('status', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('timestamp', $response);
    }

    public function testErrorEndpointHealth(): void
    {
        $this->analysisService
            ->method('getComprehensiveAnalysis')
            ->willThrowException(new \RuntimeException('Test error'));

        $controller = new AIAnalysisController($this->analysisService, $this->aiService, $this->logger);
        $controller->enableTestMode();
        
        $response = $controller->getComprehensiveAnalysis();

        // Even error responses should have proper structure
        $this->assertArrayHasKey('status', $response);
        $this->assertEquals('error', $response['status']);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('timestamp', $response);
    }
}