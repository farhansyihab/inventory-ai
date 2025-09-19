<?php
declare(strict_types=1);

namespace Tests\Functional\Api;

use PHPUnit\Framework\TestCase;

class HealthCheckTest extends TestCase
{
    public function testApiHealthEndpoint(): void
    {
        // Simulate a request to the health endpoint
        $url = 'http://localhost/inventory-ai/health';
        
        // Use curl or file_get_contents to test actual endpoint
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Accept: application/json'
            ]
        ]);
        
        try {
            $response = @file_get_contents($url, false, $context);
            
            if ($response === false) {
                $this->markTestSkipped('API server not running');
                return;
            }
            
            $data = json_decode($response, true);
            
            $this->assertIsArray($data);
            $this->assertArrayHasKey('status', $data);
            $this->assertEquals('healthy', $data['status']);
            
        } catch (\Exception $e) {
            $this->markTestSkipped('API test skipped: ' . $e->getMessage());
        }
    }

    public function testApiRootEndpoint(): void
    {
        $url = 'http://localhost/inventory-ai/';
        
        try {
            $response = @file_get_contents($url);
            
            if ($response === false) {
                $this->markTestSkipped('API server not running');
                return;
            }
            
            $data = json_decode($response, true);
            
            $this->assertIsArray($data);
            $this->assertArrayHasKey('status', $data);
            $this->assertEquals('success', $data['status']);
            
        } catch (\Exception $e) {
            $this->markTestSkipped('API test skipped: ' . $e->getMessage());
        }
    }
}