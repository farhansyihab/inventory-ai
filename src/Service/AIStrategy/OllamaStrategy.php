<?php
declare(strict_types=1);

namespace App\Service\AIStrategy;

use App\Service\AIStrategy;
use App\Utility\HttpClient;
use App\Utility\Logger;
use RuntimeException;

class OllamaStrategy implements AIStrategy
{
    private HttpClient $httpClient;
    private Logger $logger;
    private string $baseUrl;
    private string $model;
    private array $defaultOptions;

    public function __construct(HttpClient $httpClient, Logger $logger, string $baseUrl = 'http://localhost:11434', string $model = 'phi3')
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->model = $model;
        $this->defaultOptions = [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ];

        $this->logger->info("OllamaStrategy initialized", [
            'base_url' => $this->baseUrl,
            'model' => $this->model
        ]);
    }

    public function analyze(array $data, string $analysisType = 'stock_prediction'): array
    {
        try {
            $this->validateData($data);
            
            $prompt = $this->buildAnalysisPrompt($data, $analysisType);
            $response = $this->callOllamaAPI($prompt);
            
            return $this->parseAnalysisResponse($response, $analysisType);

        } catch (RuntimeException $e) {
            $this->logger->error("Ollama analysis failed: " . $e->getMessage(), [
                'analysis_type' => $analysisType,
                'model' => $this->model
            ]);
            throw new RuntimeException("AI analysis failed: " . $e->getMessage());
        }
    }

    public function generate(array $data, string $reportType = 'summary'): array
    {
        try {
            $this->validateData($data);
            
            $prompt = $this->buildReportPrompt($data, $reportType);
            $response = $this->callOllamaAPI($prompt);
            
            return $this->parseReportResponse($response, $reportType);

        } catch (RuntimeException $e) {
            $this->logger->error("Ollama report generation failed: " . $e->getMessage());
            throw new RuntimeException("AI report generation failed: " . $e->getMessage());
        }
    }

    public function isAvailable(): bool
    {
        try {
            $response = $this->httpClient->get($this->baseUrl . '/api/tags', $this->defaultOptions);
            return $response['statusCode'] === 200;
        } catch (RuntimeException $e) {
            $this->logger->warning("Ollama availability check failed: " . $e->getMessage());
            return false;
        }
    }

    private function callOllamaAPI(string $prompt): array
    {
        $payload = [
            'model' => $this->model,
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'temperature' => 0.3,
                'top_p' => 0.9,
                'num_predict' => 1000
            ]
        ];

        $this->logger->debug("Calling Ollama API", [
            'model' => $this->model,
            'prompt_length' => strlen($prompt)
        ]);

        $response = $this->httpClient->post(
            $this->baseUrl . '/api/generate',
            $payload,
            $this->defaultOptions
        );

        if ($response['statusCode'] !== 200) {
            throw new RuntimeException("Ollama API returned status: " . $response['statusCode']);
        }

        if (!isset($response['body']['response'])) {
            throw new RuntimeException("Invalid response format from Ollama API");
        }

        return $response['body'];
    }

    private function buildAnalysisPrompt(array $data, string $analysisType): string
    {
        $items = $data['items'] ?? [];
        $itemsJson = json_encode($items, JSON_PRETTY_PRINT);

        switch ($analysisType) {
            case 'stock_prediction':
                return "Sebagai AI inventory analyst, analisis data inventory berikut dan berikan prediksi stok:\n\n" .
                       "Data Inventory:\n{$itemsJson}\n\n" .
                       "Format response JSON:\n" .
                       "{\n" .
                       "  \"analysis\": \"string\",\n" .
                       "  \"riskLevel\": \"low|medium|high\",\n" .
                       "  \"confidence\": 0.0-1.0,\n" .
                       "  \"recommendations\": [\"string\"],\n" .
                       "  \"timeline\": {\"depletionDate\": \"YYYY-MM-DD\", \"optimalRestockDate\": \"YYYY-MM-DD\"}\n" .
                       "}";

            case 'anomaly_detection':
                return "Deteksi anomali dalam data inventory berikut:\n\n" .
                       "Data Inventory:\n{$itemsJson}\n\n" .
                       "Format response JSON:\n" .
                       "{\n" .
                       "  \"analysis\": \"string\",\n" .
                       "  \"anomalies\": [\"string\"],\n" .
                       "  \"riskLevel\": \"low|medium|high\",\n" .
                       "  \"confidence\": 0.0-1.0\n" .
                       "}";

            default:
                return "Analisis data inventory berikut:\n\n{$itemsJson}\n\nBerikan insight yang berguna.";
        }
    }

    private function buildReportPrompt(array $data, string $reportType): string
    {
        $items = $data['items'] ?? [];
        $itemsJson = json_encode($items, JSON_PRETTY_PRINT);

        switch ($reportType) {
            case 'summary':
                return "Buat laporan summary untuk data inventory berikut:\n\n" .
                       "Data Inventory:\n{$itemsJson}\n\n" .
                       "Format response JSON:\n" .
                       "{\n" .
                       "  \"reportType\": \"summary\",\n" .
                       "  \"summary\": {\"totalItems\": number, \"totalValue\": number, \"criticalItems\": number},\n" .
                       "  \"keyFindings\": [\"string\"],\n" .
                       "  \"recommendations\": [\"string\"]\n" .
                       "}";

            case 'detailed':
                return "Buat laporan detail untuk data inventory berikut:\n\n{$itemsJson}";

            default:
                return "Buat laporan untuk data inventory berikut:\n\n{$itemsJson}";
        }
    }

    private function parseAnalysisResponse(array $response, string $analysisType): array
    {
        $aiResponse = $response['response'] ?? '';
        
        // Extract JSON dari response text
        $jsonMatch = [];
        if (preg_match('/\{[^{}]*\{[^{}]*\}[^{}]*\}|\{[^{}]*\}/', $aiResponse, $jsonMatch)) {
            $parsedData = json_decode($jsonMatch[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->validateAnalysisResult($parsedData, $analysisType);
            }
        }

        // Fallback parsing
        return $this->createFallbackAnalysis($aiResponse, $analysisType);
    }

    private function parseReportResponse(array $response, string $reportType): array
    {
        $aiResponse = $response['response'] ?? '';
        
        $jsonMatch = [];
        if (preg_match('/\{[^{}]*\{[^{}]*\}[^{}]*\}|\{[^{}]*\}/', $aiResponse, $jsonMatch)) {
            $parsedData = json_decode($jsonMatch[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->validateReportResult($parsedData, $reportType);
            }
        }

        return $this->createFallbackReport($aiResponse, $reportType);
    }

    private function validateAnalysisResult(array $result, string $analysisType): array
    {
        $defaults = [
            'analysis' => 'Analysis completed',
            'riskLevel' => 'medium',
            'confidence' => 0.8,
            'recommendations' => [],
            'timestamp' => date('c')
        ];

        $validated = array_merge($defaults, $result);
        
        // Validate riskLevel
        if (!in_array($validated['riskLevel'], ['low', 'medium', 'high'])) {
            $validated['riskLevel'] = 'medium';
        }
        
        // Validate confidence
        $validated['confidence'] = max(0, min(1, (float)($validated['confidence'] ?? 0.8)));
        
        return $validated;
    }

    private function validateReportResult(array $result, string $reportType): array
    {
        $defaults = [
            'reportType' => $reportType,
            'summary' => ['totalItems' => 0, 'totalValue' => 0, 'criticalItems' => 0],
            'keyFindings' => [],
            'recommendations' => [],
            'timestamp' => date('c')
        ];

        return array_merge($defaults, $result);
    }

    private function createFallbackAnalysis(string $aiResponse, string $analysisType): array
    {
        $this->logger->warning("Using fallback analysis parsing", ['analysis_type' => $analysisType]);
        
        return [
            'analysis' => substr($aiResponse, 0, 200) ?: 'Analysis completed',
            'riskLevel' => 'medium',
            'confidence' => 0.6,
            'recommendations' => ['Review inventory levels manually'],
            'generatedBy' => 'fallback_parser',
            'timestamp' => date('c')
        ];
    }

    private function createFallbackReport(string $aiResponse, string $reportType): array
    {
        return [
            'reportType' => $reportType,
            'summary' => ['totalItems' => 0, 'totalValue' => 0, 'criticalItems' => 0],
            'keyFindings' => [substr($aiResponse, 0, 100) ?: 'Report generated'],
            'recommendations' => ['Review report manually'],
            'generatedBy' => 'fallback_parser',
            'timestamp' => date('c')
        ];
    }

    private function validateData(array $data): void
    {
        if (!isset($data['items']) || !is_array($data['items'])) {
            throw new RuntimeException('Inventory data must contain items array');
        }

        if (empty($data['items'])) {
            throw new RuntimeException('Inventory data items cannot be empty');
        }
    }
}