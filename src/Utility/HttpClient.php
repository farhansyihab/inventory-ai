<?php
declare(strict_types=1);

namespace App\Utility;

use RuntimeException;

class HttpClient
{
    private array $defaultOptions;

    public function __construct(array $defaultOptions = [])
    {
        $this->defaultOptions = array_merge([
            'timeout' => 30,
            'follow_redirects' => true,
            'verify_ssl' => true,
            'headers' => []
        ], $defaultOptions);
    }

    public function get(string $url, array $options = []): array
    {
        return $this->request('GET', $url, null, $options);
    }

    public function post(string $url, $data = null, array $options = []): array
    {
        return $this->request('POST', $url, $data, $options);
    }

    public function put(string $url, $data = null, array $options = []): array
    {
        return $this->request('PUT', $url, $data, $options);
    }

    public function delete(string $url, array $options = []): array
    {
        return $this->request('DELETE', $url, null, $options);
    }

    private function request(string $method, string $url, $data = null, array $options = []): array
    {
        $startTime = microtime(true);
        $finalOptions = array_merge($this->defaultOptions, $options);
        
        try {
            $context = $this->createStreamContext($method, $data, $finalOptions);
            $response = $this->executeRequest($url, $context, $finalOptions);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            return [
                'statusCode' => $response['statusCode'],
                'body' => $response['body'],
                'headers' => $response['headers'],
                'durationMs' => $duration,
                'url' => $url,
                'method' => $method
            ];

        } catch (RuntimeException $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            throw new RuntimeException(
                "HTTP request failed: {$e->getMessage()} " .
                "(URL: {$url}, Method: {$method}, Duration: {$duration}ms)",
                0,
                $e
            );
        }
    }

    private function createStreamContext(string $method, $data, array $options)
    {
        $httpOptions = [
            'method' => $method,
            'timeout' => $options['timeout'],
            'follow_location' => $options['follow_redirects'],
            'ignore_errors' => true // Untuk mendapatkan response meski status code 4xx/5xx
        ];

        // Setup headers
        if (!empty($options['headers'])) {
            $httpOptions['header'] = $this->formatHeaders($options['headers']);
        }

        // Setup SSL verification
        if (!$options['verify_ssl']) {
            $httpOptions['ssl'] = [
                'verify_peer' => false,
                'verify_peer_name' => false
            ];
        }

        // Setup data untuk POST/PUT
        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            if (is_array($data)) {
                $data = json_encode($data);
                if (!isset($httpOptions['header'])) {
                    $httpOptions['header'] = '';
                }
                $httpOptions['header'] .= "Content-Type: application/json\r\n";
            }
            
            $httpOptions['content'] = $data;
            if (!isset($httpOptions['header'])) {
                $httpOptions['header'] = '';
            }
            $httpOptions['header'] .= "Content-Length: " . strlen($data) . "\r\n";
        }

        return stream_context_create(['http' => $httpOptions]);
    }

    private function executeRequest(string $url, $context, array $options): array
    {
        set_error_handler(function($severity, $message) use ($url) {
            throw new RuntimeException("HTTP request error for {$url}: {$message}");
        });

        try {
            $response = file_get_contents($url, false, $context);
            
            if ($response === false) {
                throw new RuntimeException("Failed to retrieve response from {$url}");
            }

            $statusCode = $this->getStatusCode($http_response_header);
            $headers = $this->parseHeaders($http_response_header);
            $body = $this->parseResponseBody($response, $headers);

            return [
                'statusCode' => $statusCode,
                'body' => $body,
                'headers' => $headers
            ];

        } finally {
            restore_error_handler();
        }
    }

    private function getStatusCode(array $responseHeaders): int
    {
        if (empty($responseHeaders)) {
            return 0;
        }

        $statusLine = $responseHeaders[0];
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches);
        
        return isset($matches[1]) ? (int)$matches[1] : 0;
    }

    private function parseHeaders(array $responseHeaders): array
    {
        $headers = [];
        
        foreach ($responseHeaders as $header) {
            if (strpos($header, ':') !== false) {
                [$key, $value] = explode(':', $header, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        
        return $headers;
    }

    private function parseResponseBody(string $response, array $headers): array
    {
        $contentType = $headers['Content-Type'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $decoded = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        
        // Fallback: return as text
        return ['raw' => $response];
    }

    private function formatHeaders(array $headers): string
    {
        $formatted = '';
        foreach ($headers as $key => $value) {
            $formatted .= "{$key}: {$value}\r\n";
        }
        return $formatted;
    }

    /**
     * Check if URL is reachable
     */
    public function isUrlReachable(string $url, float $timeout = 5): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD',
                'timeout' => $timeout,
                'ignore_errors' => true
            ]
        ]);

        set_error_handler(function() { /* suppress warnings */ });
        $headers = get_headers($url, context: $context);
        restore_error_handler();

        return !empty($headers) && strpos($headers[0], '200') !== false;
    }

    /**
     * Set default options
     */
    public function setDefaultOptions(array $options): void
    {
        $this->defaultOptions = array_merge($this->defaultOptions, $options);
    }

    /**
     * Get default options
     */
    public function getDefaultOptions(): array
    {
        return $this->defaultOptions;
    }
}