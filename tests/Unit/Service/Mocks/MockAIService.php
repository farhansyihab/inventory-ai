<?php
// tests/Unit/Service/Mocks/MockAIService.php

namespace Tests\Unit\Service\Mocks;

use App\Service\AIService;
use App\Utility\Logger;
use DateTime;

class MockAIService extends AIService
{
    public function __construct()
    {
        $logger = new Logger();
        parent::__construct($logger, true);
    }

    // Gunakan method yang sesuai dengan AIService asli
    public function getAnalysisHistory(): array 
    { 
        return [
            ['success' => true, 'confidence_score' => 85.5, 'strategy' => 'OllamaStrategy'],
            ['success' => true, 'confidence_score' => 92.0, 'strategy' => 'OllamaStrategy'],
            ['success' => false, 'confidence_score' => 45.0, 'strategy' => 'AdvancedAnalysisStrategy']
        ];
    }
    
    public function getActiveStrategy(): string 
    { 
        return 'OllamaStrategy'; 
    }
    
    public function getAvailableStrategies(): array 
    { 
        return ['OllamaStrategy', 'AdvancedAnalysisStrategy']; 
    }
    
    public function isAvailable(): bool 
    { 
        return true; 
    }
    
    // Hapus method yang tidak ada di AIService asli
    // public function getAnalysesByDateRange() {} // HAPUS BARIS INI
}