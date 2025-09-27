<?php
// tests/Unit/Service/Metrics/UserMetricsTest.php

namespace Tests\Unit\Service\Metrics;

use App\Service\Metrics\UserMetrics;
use Tests\Unit\Service\Mocks\MockUserService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserMetricsTest extends TestCase
{
    private UserMetrics $userMetrics;
    private MockUserService $userService;
    private LoggerInterface $logger;

    protected function setUp(): void
    {
        $this->userService = new MockUserService();
        $this->logger = $this->createMock(LoggerInterface::class);
        
        $this->userMetrics = new UserMetrics($this->userService, $this->logger);
    }

    public function testUserMetricsCreation(): void
    {
        $this->assertInstanceOf(UserMetrics::class, $this->userMetrics);
    }

    public function testGetUserMetricsReturnsArray(): void
    {
        $metrics = $this->userMetrics->getUserMetrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('demographics', $metrics);
        $this->assertArrayHasKey('roleDistribution', $metrics);
        $this->assertArrayHasKey('activity', $metrics);
        $this->assertArrayHasKey('recentActivity', $metrics);
    }

    public function testGetUserAlertsReturnsArray(): void
    {
        $alerts = $this->userMetrics->getUserAlerts();
        
        $this->assertIsArray($alerts);
    }

    public function testUserMetricsStructure(): void
    {
        $metrics = $this->userMetrics->getUserMetrics();
        
        // Test demographics structure
        $this->assertArrayHasKey('totalUsers', $metrics['demographics']);
        $this->assertArrayHasKey('activeUsers', $metrics['demographics']);
        $this->assertArrayHasKey('inactiveUsers', $metrics['demographics']);
        
        // Test roleDistribution structure
        $this->assertArrayHasKey('admin', $metrics['roleDistribution']);
        $this->assertArrayHasKey('manager', $metrics['roleDistribution']);
        $this->assertArrayHasKey('staff', $metrics['roleDistribution']);
        
        // Test activity structure
        $this->assertArrayHasKey('loginsToday', $metrics['activity']);
        $this->assertArrayHasKey('activeNow', $metrics['activity']);
        $this->assertArrayHasKey('averageSessionTime', $metrics['activity']);
    }

    public function testUserMetricsValues(): void
    {
        $metrics = $this->userMetrics->getUserMetrics();
        
        $this->assertEquals(50, $metrics['demographics']['totalUsers']);
        $this->assertEquals(2, $metrics['roleDistribution']['admin']);
        $this->assertEquals(5, $metrics['roleDistribution']['manager']);
        $this->assertEquals(43, $metrics['roleDistribution']['staff']);
    }
}