<?php
// File: src/Service/AIStrategy/OllamaStrategy.php
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
        $this->validateData($data);
        
        switch ($analysisType) {
            case 'sales_trends':
                return $this->analyzeSalesTrends($data);
            case 'inventory_turnover':
                return $this->analyzeInventoryTurnover($data);
            case 'stock_optimization':
                return $this->optimizeStockLevels($data);
            case 'purchase_recommendations':
                return $this->generatePurchaseRecommendations($data);
            case 'safety_stock':
                return $this->calculateSafetyStock($data);
            default:
                return $this->performBasicAnalysis($data, $analysisType);
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
        
        $jsonMatch = [];
        if (preg_match('/\{[^{}]*\{[^{}]*\}[^{}]*\}|\{[^{}]*\}/', $aiResponse, $jsonMatch)) {
            $parsedData = json_decode($jsonMatch[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $this->validateAnalysisResult($parsedData, $analysisType);
            }
        }

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
        
        if (!in_array($validated['riskLevel'], ['low', 'medium', 'high'])) {
            $validated['riskLevel'] = 'medium';
        }
        
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

    private function performBasicAnalysis(array $data, string $analysisType): array
    {
        try {
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

    private function analyzeSalesTrends(array $data): array
    {
        $prompt = $this->buildSalesTrendsPrompt($data);
        $response = $this->callOllamaAPI($prompt);
        
        return $this->parseSalesTrendsResponse($response);
    }

    private function analyzeInventoryTurnover(array $data): array
    {
        $prompt = $this->buildInventoryTurnoverPrompt($data);
        $response = $this->callOllamaAPI($prompt);
        
        return $this->parseInventoryTurnoverResponse($response);
    }

    private function optimizeStockLevels(array $data): array
    {
        $prompt = $this->buildStockOptimizationPrompt($data);
        $response = $this->callOllamaAPI($prompt);
        
        return $this->parseStockOptimizationResponse($response);
    }

    private function generatePurchaseRecommendations(array $data): array
    {
        $prompt = $this->buildPurchaseRecommendationsPrompt($data);
        $response = $this->callOllamaAPI($prompt);
        
        return $this->parsePurchaseRecommendationsResponse($response);
    }

    private function calculateSafetyStock(array $data): array
    {
        $prompt = $this->buildSafetyStockPrompt($data);
        $response = $this->callOllamaAPI($prompt);
        
        return $this->parseSafetyStockResponse($response);
    }

    private function buildSalesTrendsPrompt(array $data): string
    {
        $salesData = isset($data['sales_data']) ? json_encode($data['sales_data'], JSON_PRETTY_PRINT) : '[]';
        $periodDays = isset($data['period_days']) ? (string)$data['period_days'] : '30';
        
        return "Analyze the following sales data and provide trend analysis:\n\n" .
               "SALES DATA:\n{$salesData}\n\n" .
               "ANALYSIS PERIOD: {$periodDays} days\n\n" .
               "Please provide analysis in this JSON format:\n" .
               "{\n" .
               "  \"trend_direction\": \"increasing|decreasing|stable\",\n" .
               "  \"growth_rate\": 0.15,\n" .
               "  \"seasonality_patterns\": [\"weekly_peak\", \"monthly_trend\"],\n" .
               "  \"predictions\": {\n" .
               "    \"next_period_sales\": 100,\n" .
               "    \"confidence\": 0.85\n" .
               "  },\n" .
               "  \"key_insights\": [\"insight1\", \"insight2\"],\n" .
               "  \"recommendations\": [\"recommendation1\", \"recommendation2\"]\n" .
               "}\n\n" .
               "Focus on identifying patterns, growth rates, and actionable insights.";
    }

    private function buildInventoryTurnoverPrompt(array $data): string
    {
        $items = isset($data['items']) ? json_encode($data['items'], JSON_PRETTY_PRINT) : '[]';
        
        return "Analyze inventory turnover for these items:\n\n" .
               "ITEMS:\n{$items}\n\n" .
               "Provide turnover analysis in this JSON format:\n" .
               "{\n" .
               "  \"turnover_analysis\": {\n" .
               "    \"item_name\": {\n" .
               "      \"turnover_rate\": 2.5,\n" .
               "      \"days_in_inventory\": 146,\n" .
               "      \"risk_level\": \"low|medium|high\",\n" .
               "      \"recommendations\": [\"action1\", \"action2\"]\n" .
               "    }\n" .
               "  },\n" .
               "  \"overall_metrics\": {\n" .
               "    \"average_turnover\": 2.1,\n" .
               "    \"slow_moving_items\": 3,\n" .
               "    \"fast_moving_items\": 7\n" .
               "  },\n" .
               "  \"efficiency_score\": 0.75\n" .
               "}";
    }

    private function buildStockOptimizationPrompt(array $data): string
    {
        $items = isset($data['items']) ? json_encode($data['items'], JSON_PRETTY_PRINT) : '[]';
        
        return "Optimize stock levels for these items:\n\n" .
               "ITEMS:\n{$items}\n\n" .
               "Provide optimization analysis in JSON format with optimal stock levels and reorder points.";
    }

    private function buildPurchaseRecommendationsPrompt(array $data): string
    {
        $items = isset($data['items']) ? json_encode($data['items'], JSON_PRETTY_PRINT) : '[]';
        
        return "Generate purchase recommendations for these items:\n\n" .
               "ITEMS:\n{$items}\n\n" .
               "Provide purchase recommendations in JSON format with quantities and priorities.";
    }

    private function buildSafetyStockPrompt(array $data): string
    {
        $items = isset($data['items']) ? json_encode($data['items'], JSON_PRETTY_PRINT) : '[]';
        
        return "Calculate safety stock levels for these items:\n\n" .
               "ITEMS:\n{$items}\n\n" .
               "Provide safety stock calculations in JSON format with recommended safety levels.";
    }

    private function parseSalesTrendsResponse(array $response): array
    {
        $content = $response['response'] ?? '';
        
        preg_match('/\{.*\}/s', $content, $matches);
        $jsonData = $matches[0] ?? '{}';
        
        $analysis = json_decode($jsonData, true) ?? [];
        
        return array_merge([
            'analysis_type' => 'sales_trends',
            'timestamp' => date('c')
        ], $analysis);
    }

    private function parseInventoryTurnoverResponse(array $response): array
    {
        $content = $response['response'] ?? '';
        
        preg_match('/\{.*\}/s', $content, $matches);
        $jsonData = $matches[0] ?? '{}';
        
        $analysis = json_decode($jsonData, true) ?? [];
        
        return array_merge([
            'analysis_type' => 'inventory_turnover',
            'timestamp' => date('c')
        ], $analysis);
    }

    private function parseStockOptimizationResponse(array $response): array
    {
        $content = $response['response'] ?? '';
        
        preg_match('/\{.*\}/s', $content, $matches);
        $jsonData = $matches[0] ?? '{}';
        
        $analysis = json_decode($jsonData, true) ?? [];
        
        return array_merge([
            'analysis_type' => 'stock_optimization',
            'timestamp' => date('c')
        ], $analysis);
    }

    private function parsePurchaseRecommendationsResponse(array $response): array
    {
        $content = $response['response'] ?? '';
        
        preg_match('/\{.*\}/s', $content, $matches);
        $jsonData = $matches[0] ?? '{}';
        
        $analysis = json_decode($jsonData, true) ?? [];
        
        return array_merge([
            'analysis_type' => 'purchase_recommendations',
            'timestamp' => date('c')
        ], $analysis);
    }

    private function parseSafetyStockResponse(array $response): array
    {
        $content = $response['response'] ?? '';
        
        preg_match('/\{.*\}/s', $content, $matches);
        $jsonData = $matches[0] ?? '{}';
        
        $analysis = json_decode($jsonData, true) ?? [];
        
        return array_merge([
            'analysis_type' => 'safety_stock',
            'timestamp' => date('c')
        ], $analysis);
    }
}