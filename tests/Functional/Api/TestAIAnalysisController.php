<?php
// File: tests/Functional/Api/TestAIAnalysisController.php
declare(strict_types=1);

namespace Tests\Functional\Api;

use App\Controller\AIAnalysisController;
use App\Service\InventoryAnalysisService;
use App\Service\AIService;
use App\Utility\Logger;

/**
 * Test wrapper untuk AIAnalysisController yang menghindari HTTP headers
 */
class TestAIAnalysisController extends AIAnalysisController
{
    private bool $testMode = false;
    private ?array $lastResponse = null;

    public function enableTestMode(): void
    {
        $this->testMode = true;
    }

    protected function jsonResponse(array $data, int $statusCode = 200): ?array
    {
        if ($this->testMode) {
            $this->lastResponse = [
                'status_code' => $statusCode,
                'body' => $data
            ];
            return $data;
        }
        
        // Fallback to parent for non-test scenarios
        return parent::jsonResponse($data, $statusCode);
    }

    public function getLastResponse(): ?array
    {
        return $this->lastResponse;
    }
}