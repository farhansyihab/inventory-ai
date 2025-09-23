<?php
declare(strict_types=1);

namespace Tests\Unit\AI;

use PHPUnit\Framework\TestCase;
use App\Service\AIService;
use App\Service\AIStrategy;
use App\Utility\Logger;
use RuntimeException;

class AIServiceTest extends TestCase
{
    private AIService $aiService;
    private $loggerMock;
    private $strategyMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->strategyMock = $this->createMock(AIStrategy::class);
        
        $this->aiService = new AIService($this->loggerMock, true);
        $this->aiService->registerStrategy('test_strategy', $this->strategyMock);
    }

    public function testAIServiceCanRegisterStrategies(): void
    {
        $strategies = $this->aiService->getAvailableStrategies();
        
        $this->assertContains('test_strategy', $strategies);
        $this->assertTrue($this->aiService->isAvailable());
    }

    public function testAIServiceCanSwitchStrategies(): void
    {
        $secondStrategy = $this->createMock(AIStrategy::class);
        $this->aiService->registerStrategy('second_strategy', $secondStrategy);
        
        $result = $this->aiService->setStrategy('second_strategy');
        
        $this->assertTrue($result);
    }

    public function testAnalyzeInventoryCallsActiveStrategy(): void
    {
        $inventoryData = [
            'items' => [
                [
                    'name' => 'Test Product',
                    'quantity' => 5,
                    'minStockLevel' => 10,
                    'price' => 100.0
                ]
            ]
        ];

        $expectedResult = [
            'analysis' => 'Test analysis',
            'riskLevel' => 'high',
            'confidence' => 0.9,
            'recommendations' => ['Restock immediately']
        ];

        $this->strategyMock
            ->expects($this->once())
            ->method('analyze')
            ->with($inventoryData, 'stock_prediction')
            ->willReturn($expectedResult);

        $result = $this->aiService->analyzeInventory($inventoryData, 'stock_prediction');

        $this->assertEquals($expectedResult, $result);
        $this->assertEquals('high', $result['riskLevel']);
    }

    public function testAnalyzeInventoryUsesFallbackWhenStrategyFails(): void
    {
        $inventoryData = [
            'items' => [
                [
                    'name' => 'Test Product',
                    'quantity' => 5,
                    'minStockLevel' => 10,
                    'price' => 100.0
                ]
            ]
        ];

        $this->strategyMock
            ->method('analyze')
            ->willThrowException(new RuntimeException('Strategy failed'));

        // Expect logger error call
        $this->loggerMock
            ->expects($this->once())
            ->method('error');

        $result = $this->aiService->analyzeInventory($inventoryData);

        $this->assertArrayHasKey('analysis', $result);
        $this->assertArrayHasKey('riskLevel', $result);
        $this->assertArrayHasKey('recommendations', $result);
        $this->assertEquals('fallback_algorithm', $result['generatedBy']);
    }

    public function testAnalyzeInventoryValidatesData(): void
    {
        $this->expectException(\InvalidArgumentException::class); // Ubah dari RuntimeException
        $this->expectExceptionMessage('Inventory data must contain items array');
        
        $invalidData = ['invalid' => 'data'];
        
        $this->aiService->analyzeInventory($invalidData);
    }

    public function testGenerateReportCallsActiveStrategy(): void
    {
        $inventoryData = [
            'items' => [
                [
                    'name' => 'Test Product',
                    'quantity' => 15,
                    'price' => 100.0
                ]
            ]
        ];

        $expectedReport = [
            'reportType' => 'summary',
            'summary' => ['totalItems' => 1, 'totalValue' => 1500.0, 'criticalItems' => 0],
            'keyFindings' => ['Inventory levels are good'],
            'recommendations' => ['Continue current management']
        ];

        $this->strategyMock
            ->expects($this->once())
            ->method('generate')
            ->with($inventoryData, 'summary')
            ->willReturn($expectedReport);

        $result = $this->aiService->generateReport($inventoryData, 'summary');

        $this->assertEquals($expectedReport, $result);
    }

    public function testPredictStockNeeds(): void
    {
        $items = [
            [
                'name' => 'Test Product',
                'quantity' => 5,
                'minStockLevel' => 10,
                'price' => 100.0
            ]
        ];

        $expectedAnalysis = [
            'analysis' => 'Stock prediction',
            'riskLevel' => 'high',
            'confidence' => 0.8,
            'recommendations' => ['Restock needed']
        ];

        $this->strategyMock
            ->method('analyze')
            ->willReturn($expectedAnalysis);

        $result = $this->aiService->predictStockNeeds($items, 30);

        $this->assertEquals($expectedAnalysis, $result);
    }

    public function testDetectAnomalies(): void
    {
        $inventoryData = [
            'items' => [
                [
                    'name' => 'Test Product',
                    'quantity' => -5, // Anomaly: negative quantity
                    'price' => 100.0
                ]
            ]
        ];

        $expectedResult = [
            'analysis' => 'Anomaly detection',
            'anomalies' => ['Negative quantity detected'],
            'riskLevel' => 'high',
            'confidence' => 0.9
        ];

        $this->strategyMock
            ->method('analyze')
            ->with($inventoryData, 'anomaly_detection')
            ->willReturn($expectedResult);

        $result = $this->aiService->detectAnomalies($inventoryData);

        $this->assertEquals($expectedResult, $result);
    }

    public function testAIServiceDisabledMode(): void
    {
        $disabledService = new AIService($this->loggerMock, false);
        
        $inventoryData = [
            'items' => [
                ['name' => 'Test', 'quantity' => 10, 'price' => 100.0]
            ]
        ];

        $result = $disabledService->analyzeInventory($inventoryData);

        $this->assertArrayHasKey('generatedBy', $result);
        $this->assertEquals('fallback_algorithm', $result['generatedBy']);
        $this->assertFalse($disabledService->isAvailable());
    }
}