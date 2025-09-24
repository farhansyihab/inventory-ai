<?php
declare(strict_types=1);

namespace Tests\Functional\Api;

use App\Controller\AIAnalysisController;
use App\Service\AIService;
use App\Service\InventoryAnalysisService;
use App\Utility\Logger;
use App\Utility\Router;
use PHPUnit\Framework\TestCase;

class AIAnalysisEndpointsTest extends TestCase
{
    private Router $router;
    private AIAnalysisController $controller;
    private Logger $logger;

    protected function setUp(): void
    {
        $this->logger = new Logger(__DIR__ . '/../../logs/api_test.log');
        
        // Create controller dengan mocked dependencies
        $mockAIService = $this->createMock(AIService::class);
        $mockAnalysisService = $this->createMock(InventoryAnalysisService::class);
        
        $this->controller = new AIAnalysisController(
            $mockAnalysisService, 
            $mockAIService, 
            $this->logger
        );
        
        $this->router = new Router();
        $this->setupRoutes();
        $this->controller->enableTestMode();
    }

    private function setupRoutes(): void
    {
        // Setup routes untuk testing tanpa HTTP server
        $this->router->group('/ai', function($router) {
            $router->get('/status', [$this->controller, 'getAIStatus']);
            $router->get('/analysis', [$this->controller, 'getComprehensiveAnalysis']);
            $router->get('/report/weekly', [$this->controller, 'generateWeeklyReport']);
            $router->get('/monitor/critical', [$this->controller, 'monitorCriticalItems']);
            $router->get('/predict/{days}', [$this->controller, 'predictInventoryNeeds']);
            $router->get('/health', function() {
                return ['status' => 'healthy', 'timestamp' => date('c')];
            });
        });
        $this->router->get('/health', function() {
            return ['status' => 'healthy', 'timestamp' => date('c')];
        });
        
    }

    public function testAIStatusEndpoint(): void
    {
        // Test langsung melalui router tanpa HTTP
        $result = $this->router->dispatch('GET', '/ai/status');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testComprehensiveAnalysisEndpoint(): void
    {
        $result = $this->router->dispatch('GET', '/ai/analysis');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testWeeklyReportEndpoint(): void
    {
        $result = $this->router->dispatch('GET', '/ai/report/weekly');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testHealthCheckEndpoint(): void
    {
        $result = $this->router->dispatch('GET', '/health');
        
        $this->assertIsArray($result);
        $this->assertEquals('healthy', $result['status']);
    }

    public function testPredictInventoryNeedsEndpoint(): void
    {
        $result = $this->router->dispatch('GET', '/ai/predict/7');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('status', $result);
    }

    public function testNonExistentEndpoint(): void
    {
        $result = $this->router->dispatch('GET', '/ai/nonexistent');
        
        $this->assertIsArray($result);
        $this->assertEquals('error', $result['status']);
        $this->assertEquals('Not Found', $result['message']);
    }
}