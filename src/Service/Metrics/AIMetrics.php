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

            $startDate = $this->calculateStartDate($period);
            $analyses = $this->aiService->getAnalysesByDateRange($startDate, new DateTime());

            $performanceMetrics = $this->calculatePerformanceMetrics($analyses);
            $accuracyMetrics = $this->calculateAccuracyMetrics($analyses);
            $strategyMetrics = $this->getStrategyMetrics($analyses);

            $metrics = [
                'performance' => $performanceMetrics,
                'accuracy' => $accuracyMetrics,
                'strategies' => $strategyMetrics,
                'recentAnalyses' => $this->getRecentAnalyses($analyses, 5)
            ];

            $this->logger->info('AI metrics collected successfully', [
                'totalAnalyses' => $performanceMetrics['totalAnalyses'],
                'successRate' => $performanceMetrics['successRate']
            ]);

            return $metrics;

        } catch (\Exception $e) {
            $this->logger->error('Failed to collect AI metrics', [
                'error' => $e->getMessage()
            ]);

            throw DashboardException::serviceUnavailable('AIService', $e);
        }
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

    private function getStrategyMetrics(array $analyses): array
    {
        $strategyUsage = [];
        foreach ($analyses as $analysis) {
            $strategy = $analysis['strategy'] ?? 'unknown';
            $strategyUsage[$strategy] = ($strategyUsage[$strategy] ?? 0) + 1;
        }

        $activeStrategy = $this->aiService->getActiveStrategy();

        return [
            'active' => $activeStrategy,
            'available' => $this->aiService->getAvailableStrategies(),
            'usage' => $strategyUsage
        ];
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
        $metrics = $this->getAIMetrics('1d');
        $alerts = [];

        if ($metrics['performance']['successRate'] < 80) {
            $alerts[] = [
                'type' => 'ai',
                'level' => 'critical',
                'title' => 'Low AI Success Rate',
                'message' => sprintf('AI success rate is %.1f%% today', $metrics['performance']['successRate']),
                'actionUrl' => '/ai/analyses?filter=failed'
            ];
        } elseif ($metrics['performance']['successRate'] < 90) {
            $alerts[] = [
                'type' => 'ai',
                'level' => 'warning',
                'title' => 'AI Performance Degradation',
                'message' => sprintf('AI success rate is below 90%% (%.1f%%)', $metrics['performance']['successRate']),
                'actionUrl' => '/ai/analyses'
            ];
        }

        if ($metrics['accuracy']['averageConfidence'] < 60) {
            $alerts[] = [
                'type' => 'ai',
                'level' => 'warning',
                'title' => 'Low Confidence Scores',
                'message' => sprintf('Average confidence score is %.1f%%', $metrics['accuracy']['averageConfidence']),
                'actionUrl' => '/ai/strategies'
            ];
        }

        if (!$this->aiService->isAvailable()) {
            $alerts[] = [
                'type' => 'ai',
                'level' => 'critical',
                'title' => 'AI Service Unavailable',
                'message' => 'AI service is currently unavailable',
                'actionUrl' => '/system/status'
            ];
        }

        return $alerts;
    }
}