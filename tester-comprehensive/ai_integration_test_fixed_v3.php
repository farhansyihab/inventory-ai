<?php
declare(strict_types=1);

require_once '/var/www/html/inventory-ai/vendor/autoload.php';

use App\Utility\Logger;

class AIIntegrationTest
{
    private Logger $logger;
    private array $results = [];

    public function __construct()
    {
        $this->logger = new Logger('/var/www/html/inventory-ai/tester-comprehensive/logs/ai_test.log');
    }

    public function runAITests(): void
    {
        $this->log("Starting AI Integration Tests with Ollama...");
        
        if (!$this->isOllamaAvailable()) {
            $this->log("❌ Ollama is not available. Skipping AI tests.");
            exit(0);
        }

        // Tests dengan timeout yang lebih realistic
        $tests = [
            'testOllamaConnection' => ['Ollama Connection Test', 10],
            'testSimplePrompt' => ['Simple Prompt Test', 30],
            'testShortInventoryAnalysis' => ['Short Inventory Analysis', 45],
            'testShortDemandPrediction' => ['Short Demand Prediction', 40],
            'testQuickReorderingLogic' => ['Quick Reordering Logic', 35]
        ];

        foreach ($tests as $method => [$description, $timeout]) {
            $this->runTest($method, $description, $timeout);
        }

        $this->generateReport();
    }

    private function isOllamaAvailable(): bool
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:11434/api/tags");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    private function runTest(string $method, string $description, int $timeout): void
    {
        $this->log("Running: $description (timeout: {$timeout}s)");
        
        try {
            $startTime = microtime(true);
            $result = $this->$method($timeout);
            $endTime = microtime(true);
            
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            
            $this->results[$description] = [
                'status' => 'PASS',
                'time' => $executionTime,
                'details' => $result
            ];
            
            $this->log("✓ $description ({$executionTime}ms)");
            
        } catch (Exception $e) {
            $this->results[$description] = [
                'status' => 'FAIL',
                'error' => $e->getMessage()
            ];
            $this->log("✗ $description failed: " . $e->getMessage());
        }
    }

    private function testOllamaConnection(): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:11434/api/tags");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Ollama connection failed. HTTP Code: $httpCode");
        }

        $data = json_decode($response, true);
        $models = array_column($data['models'], 'name');
        return "Available models: " . implode(', ', $models);
    }

    private function testSimplePrompt(int $timeout): string
    {
        $prompt = "Hello, respond briefly in 2-3 words.";
        $response = $this->callOllamaAPI($prompt, $timeout);
        
        if (empty($response)) {
            throw new Exception("Empty response from simple prompt");
        }

        return "Response: " . substr($response, 0, 30);
    }

    private function testShortInventoryAnalysis(int $timeout): string
    {
        // Prompt yang lebih singkat dan spesifik
        $prompt = "Inventory: Laptop(15/10), Mouse(45/20), Keyboard(12/15), Monitor(8/5). Which needs reorder? Answer in 10 words max.";

        $analysis = $this->callOllamaAPI($prompt, $timeout);
        
        if (empty($analysis)) {
            throw new Exception("Empty analysis response");
        }

        return "Analysis: " . substr($analysis, 0, 50);
    }

    private function testShortDemandPrediction(int $timeout): string
    {
        // Prompt yang lebih sederhana
        $prompt = "Sales: Jan120, Feb145, Mar130, Apr160, May155, Jun170. Next month prediction? Number only.";

        $prediction = $this->callOllamaAPI($prompt, $timeout);
        
        if (empty($prediction)) {
            throw new Exception("Empty prediction response");
        }

        // Coba extract angka
        if (preg_match('/\d+/', $prediction, $matches)) {
            return "Prediction: " . $matches[0];
        }

        return "Response: " . substr($prediction, 0, 30);
    }

    private function testQuickReorderingLogic(int $timeout): string
    {
        // Single scenario, prompt singkat
        $prompt = "Reorder: Headphones stock=5, sales=12/week, lead=14 days. Yes/No?";

        $response = $this->callOllamaAPI($prompt, $timeout);
        
        if (empty($response)) {
            throw new Exception("Empty response");
        }

        return "Decision: " . substr($response, 0, 30);
    }

    private function callOllamaAPI(string $prompt, int $timeout): string
    {
        $data = [
            'model' => 'phi3',
            'prompt' => $prompt,
            'stream' => false,
            'options' => [
                'num_predict' => 50, // Limit output length
                'temperature' => 0.1 // Less creative, more deterministic
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "http://localhost:11434/api/generate");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Ollama API failed. HTTP Code: $httpCode, Error: $curlError");
        }

        $responseData = json_decode($response, true);
        return $responseData['response'] ?? '';
    }

    private function generateReport(): void
    {
        $this->log("\n" . str_repeat("=", 60));
        $this->log("AI INTEGRATION TEST REPORT");
        $this->log(str_repeat("=", 60));
        
        $passed = 0;
        $failed = 0;
        
        foreach ($this->results as $test => $result) {
            if ($result['status'] === 'PASS') {
                $this->log(sprintf("✓ %-25s %8.2f ms  %s",
                    substr($test, 0, 25),
                    $result['time'],
                    substr($result['details'], 0, 30) . "..."
                ));
                $passed++;
            } else {
                $this->log(sprintf("✗ %-25s FAILED: %s",
                    substr($test, 0, 25),
                    substr($result['error'], 0, 40)
                ));
                $failed++;
            }
        }
        
        $this->log(str_repeat("=", 60));
        $this->log("Results: $passed passed, $failed failed");
        
        if ($failed === 0) {
            $this->log("✅ All AI integration tests passed!");
        } else {
            $this->log("⚠️  AI integration tests completed with $failed failures");
        }
    }

    private function log(string $message): void
    {
        echo $message . "\n";
        $this->logger->info($message);
    }
}

// Run the tests
$test = new AIIntegrationTest();
$test->runAITests();