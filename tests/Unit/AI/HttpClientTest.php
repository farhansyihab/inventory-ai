<?php
declare(strict_types=1);

namespace Tests\Unit\AI;

use PHPUnit\Framework\TestCase;
use App\Utility\HttpClient;
use RuntimeException;

class HttpClientTest extends TestCase
{
    private HttpClient $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = new HttpClient([
            'timeout' => 10,
            'verify_ssl' => false // Disable SSL verification for tests
        ]);
    }

    public function testHttpClientCanBeInitialized(): void
    {
        $this->assertInstanceOf(HttpClient::class, $this->httpClient);
    }

    public function testGetRequestReturnsExpectedStructure(): void
    {
        // Test dengan httpbin.org (public test service)
        $url = 'https://httpbin.org/get';
        
        $response = $this->httpClient->get($url);
        
        $this->assertArrayHasKey('statusCode', $response);
        $this->assertArrayHasKey('body', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('durationMs', $response);
        
        $this->assertEquals(200, $response['statusCode']);
        $this->assertIsArray($response['body']);
    }

    public function testPostRequestWithJSONData(): void
    {
        $url = 'https://httpbin.org/post';
        $testData = ['test' => 'value', 'number' => 123];
        
        $response = $this->httpClient->post($url, $testData);
        
        $this->assertEquals(200, $response['statusCode']);
        $this->assertArrayHasKey('json', $response['body']);
        $this->assertEquals($testData, $response['body']['json']);
    }

    public function testPutRequest(): void
    {
        $url = 'https://httpbin.org/put';
        $testData = ['message' => 'test put'];
        
        $response = $this->httpClient->put($url, $testData);
        
        $this->assertEquals(200, $response['statusCode']);
        $this->assertEquals($testData, $response['body']['json'] ?? []);
    }

    public function testDeleteRequest(): void
    {
        $url = 'https://httpbin.org/delete';
        
        $response = $this->httpClient->delete($url);
        
        $this->assertEquals(200, $response['statusCode']);
    }

    public function testRequestWithCustomHeaders(): void
    {
        $url = 'https://httpbin.org/headers';
        $options = [
            'headers' => [
                'X-Custom-Header' => 'TestValue',
                'User-Agent' => 'InventoryAITest/1.0'
            ]
        ];
        
        $response = $this->httpClient->get($url, $options);
        
        $this->assertEquals(200, $response['statusCode']);
        $this->assertStringContainsString(
            'TestValue', 
            json_encode($response['body']['headers'] ?? [])
        );
    }

    public function testRequestHandles404Error(): void
    {
        $url = 'https://httpbin.org/status/404';
        
        $response = $this->httpClient->get($url);
        
        $this->assertEquals(404, $response['statusCode']);
    }

    public function testRequestHandles500Error(): void
    {
        $url = 'https://httpbin.org/status/500';
        
        $response = $this->httpClient->get($url);
        
        $this->assertEquals(500, $response['statusCode']);
    }

    public function testRequestThrowsExceptionOnInvalidURL(): void
    {
        $this->expectException(RuntimeException::class);
        
        $invalidUrl = 'invalid://url';
        $this->httpClient->get($invalidUrl);
    }

    public function testRequestIncludesDuration(): void
    {
        $url = 'https://httpbin.org/delay/1'; // 1 second delay
        
        $response = $this->httpClient->get($url);
        
        $this->assertArrayHasKey('durationMs', $response);
        $this->assertIsFloat($response['durationMs']);
        $this->assertGreaterThan(0, $response['durationMs']);
    }

    public function testIsUrlReachableReturnsTrueForValidURL(): void
    {
        $result = $this->httpClient->isUrlReachable('https://httpbin.org');
        
        $this->assertTrue($result);
    }

    public function testIsUrlReachableReturnsFalseForInvalidURL(): void
    {
        $result = $this->httpClient->isUrlReachable('https://invalid-url-that-does-not-exist-12345.com');
        
        $this->assertFalse($result);
    }

    public function testSetDefaultOptions(): void
    {
        $newOptions = ['timeout' => 15, 'follow_redirects' => false];
        $this->httpClient->setDefaultOptions($newOptions);
        
        $currentOptions = $this->httpClient->getDefaultOptions();
        
        $this->assertEquals(15, $currentOptions['timeout']);
        $this->assertFalse($currentOptions['follow_redirects']);
    }

    public function testJSONResponseParsing(): void
    {
        $url = 'https://httpbin.org/json';
        
        $response = $this->httpClient->get($url);
        
        $this->assertEquals(200, $response['statusCode']);
        $this->assertArrayHasKey('slideshow', $response['body']);
    }

    public function testNonJSONResponseHandling(): void
    {
        $url = 'https://httpbin.org/html';
        
        $response = $this->httpClient->get($url);
        
        $this->assertEquals(200, $response['statusCode']);
        $this->assertArrayHasKey('raw', $response['body']);
        $this->assertStringContainsString('<!DOCTYPE html>', $response['body']['raw']);
    }
}