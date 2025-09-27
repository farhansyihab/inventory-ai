<?php
// src/Service/Metrics/AIMetrics.php

namespace App\Service\Metrics;

use App\Service\AIService;
use App\Exception\DashboardException;
use Psr\Log\LoggerInterface;
use DateTime;

class AIMetrics
{
    private AIService $aiService;
    private LoggerInterface $logger;

    public function __construct(AIService $aiService, LoggerInterface $logger)
    {
        $this->aiService = $aiService;
        $this->logger = $logger;
    }

    public function getAIMetrics(string $period = '7d'): array
    {
        try {
            $this->logger->info('Collecting AI metrics', ['period' => $period]);

            // Coba beberapa method yang mungkin ada
            $analyses = $this->getAnalysesData();
            
            $performanceMetrics = $this->calculatePerformanceMetrics($analyses);
            $accuracyMetrics = $this->calculateAccuracyMetrics($analyses);
            $strategyMetrics = $this->getStrategyMetrics();

            $metrics = [
                'performance' => $performanceMetrics,
                'accuracy' => $accuracyMetrics,
                'strategies' => $strategyMetrics,
                'recentAnalyses' => $this->getRecentAnalyses($analyses, 5)
            ];

            $this->logger->info('AI metrics collected successfully');
            return $metrics;

        } catch (\Exception $e) {
            $this->logger->warning('Failed to collect AI metrics, using fallback', [
                'error' => $e->getMessage()
            ]);

            // Return fallback data instead of throwing exception
            return $this->getFallbackAIMetrics();
        }
    }

    private function getFallbackAIMetrics(): array
    {
        return [
            'performance' => [
                'totalAnalyses' => 0,
                'successfulAnalyses' => 0,
                'failedAnalyses' => 0,
                'successRate' => 0
            ],
            'accuracy' => [
                'averageConfidence' => 0,
                'highConfidence' => 0,
                'mediumConfidence' => 0,
                'lowConfidence' => 0
            ],
            'strategies' => [
                'active' => 'unknown',
                'available' => [],
                'usage' => []
            ],
            'recentAnalyses' => []
        ];
    }

    private function getAnalysesData(): array
    {
        // Coba berbagai method yang mungkin ada
        $methodsToTry = [
            'getAnalysisHistory',
            'getAnalysesByDateRange', 
            'getRecentAnalyses',
            'getAllAnalyses'
        ];

        foreach ($methodsToTry as $method) {
            if (method_exists($this->aiService, $method)) {
                try {
                    if ($method === 'getAnalysesByDateRange') {
                        $startDate = $this->calculateStartDate('7d');
                        return $this->aiService->$method($startDate, new DateTime());
                    }
                    return $this->aiService->$method();
                } catch (\Exception $e) {
                    continue; // Coba method berikutnya
                }
            }
        }

        // Jika tidak ada method yang berhasil, return array kosong
        return [];
    }

    private function calculateStartDate(string $period): DateTime
    {
        return match ($period) {
            '1d' => new DateTime('-1 day'),
            '7d' => new DateTime('-7 days'),
            '30d' => new DateTime('-30 days'),
            default => new DateTime('-7 days'),
        };
    }

    private function calculatePerformanceMetrics(array $analyses): array
    {
        $totalAnalyses = count($analyses);
        $successfulAnalyses = count(array_filter($analyses, fn($a) => $a['success'] ?? false));
        $failedAnalyses = $totalAnalyses - $successfulAnalyses;

        return [
            'totalAnalyses' => $totalAnalyses,
            'successfulAnalyses' => $successfulAnalyses,
            'failedAnalyses' => $failedAnalyses,
            'successRate' => MetricsCalculator::calculateSuccessRate($successfulAnalyses, $totalAnalyses)
        ];
    }

    private function calculateAccuracyMetrics(array $analyses): array
    {
        $confidenceScores = array_filter(array_column($analyses, 'confidence_score'));
        
        $highConfidence = count(array_filter($confidenceScores, fn($score) => $score >= 80));
        $mediumConfidence = count(array_filter($confidenceScores, fn($score) => $score >= 60 && $score < 80));
        $lowConfidence = count(array_filter($confidenceScores, fn($score) => $score < 60));

        return [
            'averageConfidence' => MetricsCalculator::calculateAverageConfidence($analyses),
            'highConfidence' => $highConfidence,
            'mediumConfidence' => $mediumConfidence,
            'lowConfidence' => $lowConfidence
        ];
    }

    private function getStrategyMetrics(): array
    {
        try {
            $activeStrategy = 'unknown';
            $availableStrategies = [];
            
            if (method_exists($this->aiService, 'getActiveStrategy')) {
                $activeStrategy = $this->aiService->getActiveStrategy();
            } elseif (method_exists($this->aiService, 'getActiveStrategyName')) {
                $activeStrategy = $this->aiService->getActiveStrategyName();
            }
            
            if (method_exists($this->aiService, 'getAvailableStrategies')) {
                $availableStrategies = $this->aiService->getAvailableStrategies();
            } elseif (method_exists($this->aiService, 'getAvailableStrategyNames')) {
                $availableStrategies = $this->aiService->getAvailableStrategyNames();
            }

            return [
                'active' => $activeStrategy,
                'available' => $availableStrategies,
                'usage' => []
            ];
        } catch (\Exception $e) {
            return [
                'active' => 'unknown',
                'available' => [],
                'usage' => []
            ];
        }
    }

    private function getRecentAnalyses(array $analyses, int $limit): array
    {
        usort($analyses, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
        
        return array_slice(array_map(function($analysis) {
            return [
                'id' => $analysis['id'] ?? 'unknown',
                'type' => $analysis['type'] ?? 'unknown',
                'strategy' => $analysis['strategy'] ?? 'unknown',
                'confidence' => $analysis['confidence_score'] ?? 0,
                'duration' => $analysis['duration'] ?? '0s',
                'timestamp' => $analysis['timestamp'] ?? (new DateTime())->format(DateTime::ATOM)
            ];
        }, $analyses), 0, $limit);
    }

    public function getAIAlerts(): array
    {
        try {
            $metrics = $this->getAIMetrics('1d');
            $alerts = [];

            if (($metrics['performance']['successRate'] ?? 0) < 80) {
                $alerts[] = [
                    'type' => 'ai',
                    'level' => 'critical',
                    'title' => 'Low AI Success Rate',
                    'message' => 'AI success rate is low',
                    'actionUrl' => '/ai/analyses'
                ];
            }

            return $alerts;
        } catch (\Exception $e) {
            return []; // Return empty array instead of throwing
        }
    }
}