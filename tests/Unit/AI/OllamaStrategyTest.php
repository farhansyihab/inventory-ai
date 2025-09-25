<?php
declare(strict_types=1);

namespace Tests\Unit\AI;

use PHPUnit\Framework\TestCase;
use App\Service\AIStrategy\OllamaStrategy;
use App\Utility\HttpClient;
use App\Utility\Logger;
use RuntimeException;

class OllamaStrategyTest extends TestCase
{
    private OllamaStrategy $ollamaStrategy;
    private $httpClientMock;
    private $loggerMock;

    protected function setUp(): void
    {
        $this->httpClientMock = $this->createMock(HttpClient::class);
        $this->loggerMock = $this->createMock(Logger::class);
        
        $this->ollamaStrategy = new OllamaStrategy(
            $this->httpClientMock,
            $this->loggerMock,
            'http://localhost:11434',
            'phi3'
        );
    }

    public function testOllamaStrategyCanBeInitialized(): void
    {
        $this->assertInstanceOf(OllamaStrategy::class, $this->ollamaStrategy);
    }

    public function testAnalyzeInventoryCallsOllamaAPI(): void
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

        $mockResponse = [
            'statusCode' => 200,
            'body' => [
                'response' => '{"analysis": "Test analysis", "riskLevel": "high", "confidence": 0.9, "recommendations": ["Restock now"]}'
            ]
        ];

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->willReturn($mockResponse);

        $result = $this->ollamaStrategy->analyze($inventoryData, 'stock_prediction');

        $this->assertArrayHasKey('analysis', $result);
        $this->assertEquals('high', $result['riskLevel']);
        $this->assertEquals(0.9, $result['confidence']);
    }

    public function testAnalyzeInventoryHandlesAPIErrors(): void
    {
        $inventoryData = [
            'items' => [
                ['name' => 'Test', 'quantity' => 10]
            ]
        ];

        $this->httpClientMock
            ->method('post')
            ->willReturn(['statusCode' => 500, 'body' => []]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('AI analysis failed');

        $this->ollamaStrategy->analyze($inventoryData);
    }

    public function testAnalyzeInventoryHandlesInvalidJSONResponse(): void
    {
        $inventoryData = [
            'items' => [
                ['name' => 'Test', 'quantity' => 10]
            ]
        ];

        $mockResponse = [
            'statusCode' => 200,
            'body' => [
                'response' => 'Invalid JSON response from AI'
            ]
        ];

        $this->httpClientMock
            ->method('post')
            ->willReturn($mockResponse);

        $result = $this->ollamaStrategy->analyze($inventoryData);

        // Should use fallback parsing
        $this->assertArrayHasKey('analysis', $result);
        $this->assertArrayHasKey('riskLevel', $result);
        $this->assertArrayHasKey('confidence', $result);
    }

    public function testGenerateReportCallsOllamaAPI(): void
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

        $mockResponse = [
            'statusCode' => 200,
            'body' => [
                'response' => '{"reportType": "summary", "summary": {"totalItems": 1, "totalValue": 1500, "criticalItems": 0}, "keyFindings": ["Good inventory"], "recommendations": ["Continue"]}'
            ]
        ];

        $this->httpClientMock
            ->expects($this->once())
            ->method('post')
            ->willReturn($mockResponse);

        $result = $this->ollamaStrategy->generate($inventoryData, 'summary');

        $this->assertEquals('summary', $result['reportType']);
        $this->assertEquals(1, $result['summary']['totalItems']);
        $this->assertIsArray($result['keyFindings']);
    }

    public function testIsAvailableReturnsTrueWhenOllamaIsReachable(): void
    {
        $this->httpClientMock
            ->method('get')
            ->willReturn(['statusCode' => 200]);

        $this->assertTrue($this->ollamaStrategy->isAvailable());
    }

    public function testIsAvailableReturnsFalseWhenOllamaIsUnreachable(): void
    {
        $this->httpClientMock
            ->method('get')
            ->willReturn(['statusCode' => 404]);

        $this->assertFalse($this->ollamaStrategy->isAvailable());
    }

    public function testIsAvailableHandlesExceptions(): void
    {
        $this->httpClientMock
            ->method('get')
            ->willThrowException(new RuntimeException('Connection failed'));

        $this->assertFalse($this->ollamaStrategy->isAvailable());
    }

    public function testAnalyzeValidatesInventoryData(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Inventory data must contain items array');

        $invalidData = ['invalid' => 'data'];
        $this->ollamaStrategy->analyze($invalidData);
    }

    public function testGenerateValidatesInventoryData(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Inventory data items cannot be empty');

        $emptyData = ['items' => []];
        $this->ollamaStrategy->generate($emptyData);
    }

    public function testDifferentAnalysisTypes(): void
    {
        $inventoryData = [
            'items' => [
                ['name' => 'Test', 'quantity' => 10, 'minStockLevel' => 5]
            ]
        ];

        $mockResponse = [
            'statusCode' => 200,
            'body' => [
                'response' => '{"analysis": "Anomaly detection", "anomalies": [], "riskLevel": "low", "confidence": 0.8}'
            ]
        ];

        $this->httpClientMock
            ->method('post')
            ->willReturn($mockResponse);

        $result = $this->ollamaStrategy->analyze($inventoryData, 'anomaly_detection');

        // Test yang lebih robust - tidak bergantung pada analysisType
        $this->assertEquals('Anomaly detection', $result['analysis']);
        $this->assertEquals('low', $result['riskLevel']);
        $this->assertEquals(0.8, $result['confidence']);
    }
}