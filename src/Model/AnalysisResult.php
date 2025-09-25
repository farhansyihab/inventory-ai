<?php
// File: src/Model/AnalysisResult.php
declare(strict_types=1);

namespace App\Model;

class AnalysisResult
{
    public function __construct(
        private string $analysisType,
        private array $findings,
        private array $recommendations,
        private float $confidenceScore,
        private array $supportingData = [],
        private bool $isFallback = false,
        private ?string $errorMessage = null
    ) {}

    public function getAnalysisType(): string
    {
        return $this->analysisType;
    }

    public function getFindings(): array
    {
        return $this->findings;
    }

    public function getRecommendations(): array
    {
        return $this->recommendations;
    }

    public function getConfidenceScore(): float
    {
        return $this->confidenceScore;
    }

    public function getSupportingData(): array
    {
        return $this->supportingData;
    }

    public function isFallback(): bool
    {
        return $this->isFallback;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function toArray(): array
    {
        return [
            'analysis_type' => $this->analysisType,
            'findings' => $this->findings,
            'recommendations' => $this->recommendations,
            'confidence_score' => $this->confidenceScore,
            'supporting_data' => $this->supportingData,
            'is_fallback' => $this->isFallback,
            'error_message' => $this->errorMessage,
            'timestamp' => date('c')
        ];
    }

    public function isValid(): bool
    {
        return $this->confidenceScore > 0 && empty($this->errorMessage);
    }

    public function getSummary(): string
    {
        $status = $this->isValid() ? 'Valid' : 'Invalid';
        $fallback = $this->isFallback ? ' (Fallback)' : '';
        
        return sprintf(
            "%s Analysis - Confidence: %.1f%%%s",
            ucfirst(str_replace('_', ' ', $this->analysisType)),
            $this->confidenceScore * 100,
            $fallback
        );
    }
}
?>