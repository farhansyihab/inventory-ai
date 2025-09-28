<?php
// tests/Integration/Controller/DashboardControllerTest.php

namespace Tests\Integration\Controller;

use App\Controller\DashboardController;
use App\Service\DashboardService;
use App\Model\DashboardMetrics;
use App\Exception\DashboardException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Psr\Log\LoggerInterface;
use DateTime;

class DashboardControllerTest extends TestCase
{
    private DashboardController $controller;
    private DashboardService $dashboardService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->dashboardService = $this->createMock(DashboardService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->controller = new DashboardController($this->dashboardService, $this->logger);
    }

    public function testGetMetricsSuccess(): void
    {
        $metrics = new DashboardMetrics(new DateTime(), ['totalItems' => 100]);
        
        $this->dashboardService->method('getDashboardMetrics')
            ->willReturn($metrics);

        $request = new Request(['detailed' => 'true']);
        $response = $this->controller->getMetrics($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals(100, $data['data']['inventory']['totalItems']);
        $this->assertArrayHasKey('meta', $data);
    }

    public function testGetMetricsServiceUnavailable(): void
    {
        $this->dashboardService->method('getDashboardMetrics')
            ->willThrowException(DashboardException::serviceUnavailable('InventoryService'));

        $request = new Request();
        $response = $this->controller->getMetrics($request);

        $this->assertEquals(503, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('DASH_001', $data['error']['code']);
    }

    public function testGetHealth(): void
    {
        $metrics = new DashboardMetrics(new DateTime(), [], [], [], [], [], []);
        
        $this->dashboardService->method('getDashboardMetrics')
            ->willReturn($metrics);

        $request = new Request();
        $response = $this->controller->getHealth($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('healthy', $data['status']);
    }

    public function testClearCache(): void
    {
        $this->dashboardService->expects($this->once())
            ->method('clearCache');

        $request = new Request();
        $response = $this->controller->clearCache($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
    }

    public function testGetCacheStats(): void
    {
        $stats = ['entries' => 5, 'hits' => 10, 'misses' => 2];
        
        $this->dashboardService->method('getCacheStats')
            ->willReturn($stats);

        $request = new Request();
        $response = $this->controller->getCacheStats($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertTrue($data['success']);
        $this->assertEquals($stats, $data['data']);
    }

    public function testUnexpectedError(): void
    {
        $this->dashboardService->method('getDashboardMetrics')
            ->willThrowException(new \Exception('Unexpected error'));

        $this->logger->expects($this->once())
            ->method('critical');

        $request = new Request();
        $response = $this->controller->getMetrics($request);

        $this->assertEquals(500, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('INTERNAL_ERROR', $data['error']['code']);
    }
}