<?php
declare(strict_types=1);

namespace Tests\Unit\AI;

use PHPUnit\Framework\TestCase;
use App\Utility\HttpClient;
use RuntimeException;

class HttpClientTest extends TestCase
{
    private HttpClient $httpClient;
    private string $baseUrl;
    private bool $skipTests = false;

    protected function setUp(): void
    {
        // Cari available service
        $this->baseUrl = $this->getAvailableTestService();
        
        if ($this->skipTests) {
            return; // Already skipped in getAvailableTestService()
        }
        
        $this->httpClient = new HttpClient([
            'timeout' => 10,
            'verify_ssl' => false
        ]);
    }

    /**
     * Get first available test service from fallback list
     */
    private function getAvailableTestService(): string
    {
        $services = [
            [
                'url' => 'https://httpbin.org',
                'name' => 'httpbin.org', 
                'test_path' => '/get', // Use universal endpoint
                'type' => 'httpbin'
            ],
            [
                'url' => 'https://httpbun.com',
                'name' => 'httpbun.com',
                'test_path' => '/get', // Use universal endpoint
                'type' => 'httpbun'
            ],            
            [
                'url' => 'https://postman-echo.com',
                'name' => 'postman-echo.com',
                'test_path' => '/get',
                'type' => 'postman'
            ]
        ];

        foreach ($services as $service) {
            if ($this->isServiceAvailable($service['url'] . $service['test_path'])) {
                echo "\n✅ Using test service: {$service['name']} ({$service['type']})";
                return $service['url'];
            }
            
            echo "\n❌ Service unavailable: {$service['name']}";
        }

        $this->markTestSkipped('No test services available');
        $this->skipTests = true;
        return '';
    }

    /**
     * Check if a service is available
     */
    private function isServiceAvailable(string $testUrl): bool
    {
        $context = stream_context_create([
            'http' => ['timeout' => 3],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        // Suppress warnings
        $response = @file_get_contents($testUrl, false, $context);
        
        return $response !== false;
    }

    /**
     * Skip test if no services available
     */
    private function skipIfNoServicesAvailable(): void
    {
        if ($this->skipTests) {
            $this->markTestSkipped('No test services available');
        }
    }

    /**
     * Build URL with service-specific paths
     */
    private function buildUrl(string $path): string
    {
        // Handle service-specific path mappings
        $pathMappings = [
            'httpbun.com' => [
                '/status/' => '/status/',
                '/delay/' => '/delay/'
            ],
            'httpbin.org' => [
                '/status/' => '/status/',
                '/delay/' => '/delay/'
            ],
            'postman-echo.com' => [
                '/status/' => '/status/', // Postman doesn't have status endpoints
                '/delay/' => '/delay?seconds='
            ]
        ];

        $host = parse_url($this->baseUrl, PHP_URL_HOST);
        
        // Apply path mappings if available
        foreach ($pathMappings[$host] ?? [] as $from => $to) {
            if (str_starts_with($path, $from)) {
                $value = substr($path, strlen($from));
                return $this->baseUrl . $to . $value;
            }
        }

        return $this->baseUrl . $path;
    }

    // ===== TEST METHODS =====

    public function testHttpClientCanBeInitialized(): void
    {
        $this->skipIfNoServicesAvailable();
        $this->assertInstanceOf(HttpClient::class, $this->httpClient);
    }

    public function testGetRequestReturnsExpectedStructure(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $url = $this->buildUrl('/get');
        $response = $this->httpClient->get($url);
        
        $this->assertArrayHasKey('statusCode', $response);
        $this->assertArrayHasKey('body', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('durationMs', $response);
        
        $this->assertEquals(200, $response['statusCode']);
    }

    public function testPostRequestWithJSONData(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $url = $this->buildUrl('/post');
        $testData = ['test' => 'value', 'number' => 123];
        
        $response = $this->httpClient->post($url, $testData);
        
        $this->assertEquals(200, $response['statusCode']);
        
        // Different services return data in different structures
        $body = $response['body'];
        if (isset($body['json'])) {
            $this->assertEquals($testData, $body['json']);
        } elseif (isset($body['data'])) {
            $this->assertEquals($testData, $body['data']);
        } else {
            // At least check if response contains our data
            $this->assertStringContainsString('"test":"value"', json_encode($body));
        }
    }

    public function testPutRequest(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $url = $this->buildUrl('/put');
        $testData = ['message' => 'test put'];
        
        $response = $this->httpClient->put($url, $testData);
        
        $this->assertEquals(200, $response['statusCode']);
    }

    public function testDeleteRequest(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $url = $this->buildUrl('/delete');
        $response = $this->httpClient->delete($url);
        
        $this->assertEquals(200, $response['statusCode']);
    }

    public function testRequestWithCustomHeaders(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $url = $this->buildUrl('/headers');
        $options = [
            'headers' => [
                'X-Custom-Header' => 'TestValue',
                'User-Agent' => 'InventoryAITest/1.0'
            ]
        ];
        
        $response = $this->httpClient->get($url, $options);
        
        $this->assertEquals(200, $response['statusCode']);
        
        // Check if headers are reflected in response
        $bodyJson = json_encode($response['body']);
        $this->assertStringContainsString('TestValue', $bodyJson);
    }

    public function testRequestHandles404Error(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $url = $this->buildUrl('/status/404');
        $response = $this->httpClient->get($url);
        
        // Accept 404 or 400 (some services might not have exact status endpoints)
        $this->assertContains($response['statusCode'], [404, 400]);
    }

    public function testRequestHandles500Error(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $url = $this->buildUrl('/status/500');
        $response = $this->httpClient->get($url);
        
        // Accept 500, 502, or 400 (flexible for different services)
        $this->assertContains($response['statusCode'], [500, 502, 400]);
    }

    public function testRequestThrowsExceptionOnInvalidURL(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $this->expectException(RuntimeException::class);
        $invalidUrl = 'invalid://url';
        $this->httpClient->get($invalidUrl);
    }

    public function testRequestIncludesDuration(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $url = $this->buildUrl('/delay/1');
        $response = $this->httpClient->get($url);
        
        $this->assertArrayHasKey('durationMs', $response);
        $this->assertIsFloat($response['durationMs']);
        $this->assertGreaterThan(0, $response['durationMs']);
    }

    public function testIsUrlReachableReturnsTrueForValidURL(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $result = $this->httpClient->isUrlReachable($this->baseUrl);
        $this->assertTrue($result);
    }

    public function testIsUrlReachableReturnsFalseForInvalidURL(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $result = $this->httpClient->isUrlReachable('https://invalid-url-that-does-not-exist-12345.com');
        $this->assertFalse($result);
    }

    public function testSetDefaultOptions(): void
    {
        // Test ini tidak butuh external service, selalu jalan
        $httpClient = new HttpClient();
        $newOptions = ['timeout' => 15];
        $httpClient->setDefaultOptions($newOptions);
        
        $currentOptions = $httpClient->getDefaultOptions();
        $this->assertEquals(15, $currentOptions['timeout']);
    }

    public function testJSONResponseParsing(): void
    {
        $this->skipIfNoServicesAvailable();
        
        // Use universally available endpoint
        $url = $this->buildUrl('/get');
        $response = $this->httpClient->get($url);
        
        $this->assertEquals(200, $response['statusCode']);
        $this->assertIsArray($response['body']);
        
        // Basic JSON structure validation
        $this->assertArrayHasKey('url', $response['body']);
    }

    public function testNonJSONResponseHandling(): void
    {
        $this->skipIfNoServicesAvailable();
        
        $url = $this->buildUrl('/html');
        $response = $this->httpClient->get($url);
        
        $this->assertEquals(200, $response['statusCode']);
        $this->assertIsString($response['body']['raw'] ?? $response['body']);
    }

    /**
     * Test service discovery information
     */
    public function testServiceDiscoveryWorks(): void
    {
        $services = [
            'https://httpbun.com',
            'https://httpbin.org',
            'https://postman-echo.com',
            'http://localhost:8000'
        ];

        $available = [];
        foreach ($services as $service) {
            if ($this->isServiceAvailable($service . '/get')) {
                $available[] = parse_url($service, PHP_URL_HOST);
            }
        }

        $this->assertIsArray($available);
        // At least one service should be available for most tests
        if (empty($available)) {
            $this->markTestSkipped('No external services available for testing');
        }
        
        echo "\n📡 Available services: " . implode(', ', $available);
    }
}