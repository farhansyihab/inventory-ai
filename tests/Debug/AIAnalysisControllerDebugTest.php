<?php
// File: tests/Debug/AIAnalysisControllerDebugTest.php
declare(strict_types=1);

namespace Tests\Debug;

use App\Controller\AIAnalysisController;
use App\Service\InventoryAnalysisService;
use App\Service\AIService;
use App\Utility\Logger;
use PHPUnit\Framework\TestCase;

class AIAnalysisControllerDebugTest extends TestCase
{
    public function testControllerExists(): void
    {
        $logger = new Logger();
        $aiService = $this->createMock(AIService::class);
        $analysisService = $this->createMock(InventoryAnalysisService::class);
        
        // Test jika controller bisa di-instansiasi
        $controller = new AIAnalysisController($analysisService, $aiService, $logger);
        
        $this->assertInstanceOf(AIAnalysisController::class, $controller);
        $this->assertTrue(method_exists($controller, 'analyzeSalesTrends'), 
            'analyzeSalesTrends method should exist');
    }
    
    public function testSimpleSalesAnalysis(): void
    {
        $logger = new Logger();
        $aiService = $this->createMock(AIService::class);
        $analysisService = $this->createMock(InventoryAnalysisService::class);
        
        $aiService->method('analyzeSalesTrends')->willReturn([
            'trend_direction' => 'increasing',
            'growth_rate' => 0.1,
            'confidence' => 0.8
        ]);
        
        $controller = new AIAnalysisController($analysisService, $aiService, $logger);
        $controller->enableTestMode();
        
        // Test dengan data sangat sederhana
        $_POST = [
            'sales_data' => [[
                'date' => '2024-01-01',
                'quantity' => 10,
                'revenue' => 100.0
            ]],
            'period_days' => 7
        ];
        
        $response = $controller->analyzeSalesTrends();
        
        echo "\n=== SIMPLE TEST RESPONSE ===\n";
        var_export($response);
        echo "\n===========================\n";
        
        $this->assertArrayHasKey('status', $response);
    }
}