<?php
// File: tests/Unit/AI/ErrorHandlingTest.php (Fixed)
declare(strict_types=1);

namespace Tests\Unit\AI;

use App\Service\AIService;
use App\Service\AIStrategy;
use App\Utility\Logger;
use PHPUnit\Framework\TestCase;

class ErrorHandlingTest extends TestCase
{
    private AIService $aiService;
    private $loggerMock;
    private $failingStrategyMock;

    protected function setUp(): void
    {
        $this->loggerMock = $this->createMock(Logger::class);
        $this->failingStrategyMock = $this->createMock(AIStrategy::class);
        
        $this->aiService = new AIService($this->loggerMock, true);
        $this->aiService->registerStrategy('failing_strategy', $this->failingStrategyMock);
    }

    public function testFallbackOnStrategyFailure(): void
    {
        $this->failingStrategyMock
            ->method('analyze')
            ->willThrowException(new \RuntimeException('Strategy failure'));

        $this->failingStrategyMock
            ->method('isAvailable')
            ->willReturn(false);

        $this->loggerMock
            ->expects($this->atLeastOnce())
            ->method('error');

        $salesData = [
            [
                'date' => '2024-01-01',
                'quantity' => 10,
                'revenue' => 100.0
            ]
        ];

        $result = $this->aiService->analyzeSalesTrends($salesData, 30);

        $this->assertTrue($result['is_fallback']);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertLessThan(0.7, $result['confidence']);
    }

    public function testDataValidationErrors(): void
    {
        $invalidData = [
            ['invalid' => 'structure']
        ];

        $this->expectException(\InvalidArgumentException::class);
        $this->aiService->analyzeSalesTrends($invalidData, 30);
    }

    public function testEmptyDatasetHandling(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Sales data cannot be empty');
        
        $this->aiService->analyzeSalesTrends([], 30);
    }

    public function testInvalidAnalysisType(): void
    {
        $validData = [
            'items' => [
                ['name' => 'Test', 'quantity' => 10, 'price' => 100.0]
            ]
        ];

        // PERBAIKAN: Strategy harus return array yang valid
        $this->failingStrategyMock
            ->method('analyze')
            ->willReturn([
                'analysis' => 'Fallback analysis for invalid type',
                'riskLevel' => 'medium',
                'confidence' => 0.6,
                'recommendations' => []
            ]);

        $result = $this->aiService->analyzeInventory($validData, 'invalid_analysis_type');

        // PERBAIKAN: Assert struktur yang benar
        $this->assertArrayHasKey('analysis', $result);
        $this->assertArrayHasKey('riskLevel', $result);
    }

    public function testMemoryLimitHandling(): void
    {
        $largeData = $this->generateVeryLargeDataset();

        $this->failingStrategyMock
            ->method('analyze')
            ->willReturnCallback(function($data) {
                if (count($data['sales_data'] ?? []) > 10000) {
                    return [
                        'analysis_type' => 'sales_trends',
                        'trend_direction' => 'stable',
                        'is_fallback' => true
                    ];
                }
                return ['status' => 'normal_processing'];
            });

        $result = $this->aiService->analyzeSalesTrends($largeData, 30);

        $this->assertArrayHasKey('analysis_type', $result);
    }

    public function testTimeoutHandling(): void
    {
        // PERBAIKAN: Jangan gunakan sleep di test, mock dengan exception
        $this->failingStrategyMock
            ->method('analyze')
            ->willReturnCallback(function() {
                // Simulate timeout dengan exception, bukan sleep
                throw new \RuntimeException('Strategy timeout');
            });

        $this->failingStrategyMock
            ->method('isAvailable')
            ->willReturn(true);

        $salesData = $this->generateSalesData(100);

        $startTime = microtime(true);
        $result = $this->aiService->analyzeSalesTrends($salesData, 30);
        $endTime = microtime(true);

        $duration = $endTime - $startTime;

        // PERBAIKAN: Test harus complete quickly dengan fallback
        $this->assertLessThan(1.0, $duration, 
            "Analysis should use fallback quickly when strategy times out"
        );
        $this->assertTrue($result['is_fallback']);
    }

    public function testPartialFailureRecovery(): void
    {
        $this->failingStrategyMock
            ->method('analyze')
            ->willReturnCallback(function($data) {
                if (count($data['sales_data'] ?? []) > 500) {
                    throw new \RuntimeException('Strategy overloaded');
                }
                return ['status' => 'success'];
            });

        $largeData = $this->generateSalesData(600);
        $result = $this->aiService->analyzeSalesTrends($largeData, 30);

        $this->assertTrue($result['is_fallback']);
        $this->assertArrayHasKey('recommendations', $result);
    }

    private function generateVeryLargeDataset(): array
    {
        $data = [];
        for ($i = 0; $i < 15000; $i++) {
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'quantity' => rand(1, 100),
                'revenue' => rand(100, 1000) / 100
            ];
        }
        return $data;
    }

    private function generateSalesData(int $count): array
    {
        $data = [];
        for ($i = 0; $i < $count; $i++) {
            $data[] = [
                'date' => date('Y-m-d', strtotime("-$i days")),
                'quantity' => rand(1, 50),
                'revenue' => rand(50, 500) / 100
            ];
        }
        return $data;
    }
}