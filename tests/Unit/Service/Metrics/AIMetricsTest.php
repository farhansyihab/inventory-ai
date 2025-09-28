<?php
// tests/Unit/Service/Metrics/AIMetricsTest.php

namespace Tests\Unit\Service\Metrics;

use App\Service\Metrics\AIMetrics;
use Tests\Unit\Service\Mocks\MockAIService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class AIMetricsTest extends TestCase
{
    private AIMetrics $aiMetrics;
    private MockAIService $aiService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->aiService = new MockAIService();
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->aiMetrics = new AIMetrics($this->aiService, $this->logger);
    }

    public function testAIMetricsCreation(): void
    {
        $this->assertInstanceOf(AIMetrics::class, $this->aiMetrics);
    }

    public function testGetAIMetricsReturnsArray(): void
    {
        // Setup logger untuk test yang sukses
        $this->logger->expects($this->atLeastOnce())->method('info');
        
        $metrics = $this->aiMetrics->getAIMetrics('7d');
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('performance', $metrics);
        $this->assertArrayHasKey('accuracy', $metrics);
        $this->assertArrayHasKey('strategies', $metrics);
    }

    public function testGetAIMetricsWithFallback(): void
    {
        // Buat mock AIService yang akan throw exception
        $failingAiService = new class extends MockAIService {
            public function getAnalysisHistory(): array {
                throw new \Exception('Service unavailable');
            }
        };
        
        $metrics = new AIMetrics($failingAiService, $this->logger);
        
        $result = $metrics->getAIMetrics('7d');
        
        $this->assertIsArray($result);
        // Should return fallback data instead of throwing exception
        $this->assertEquals(0, $result['performance']['totalAnalyses']);
    }

    public function testGetAIAlertsReturnsArray(): void
    {
        $alerts = $this->aiMetrics->getAIAlerts();
        
        $this->assertIsArray($alerts);
    }

    public function testAIMetricsBasicStructure(): void
    {
        $metrics = $this->aiMetrics->getAIMetrics('7d');
        
        // Test basic structure
        $this->assertArrayHasKey('performance', $metrics);
        $this->assertArrayHasKey('accuracy', $metrics);
        $this->assertArrayHasKey('strategies', $metrics);
        $this->assertArrayHasKey('recentAnalyses', $metrics);
    }
}