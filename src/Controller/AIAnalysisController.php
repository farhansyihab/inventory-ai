<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\InventoryAnalysisService;
use App\Service\AIService;
use App\Utility\Logger;

class AIAnalysisController extends BaseController
{
    private InventoryAnalysisService $analysisService;
    private AIService $aiService;

    public function __construct(
        ?InventoryAnalysisService $analysisService = null,
        ?AIService $aiService = null,
        ?Logger $logger = null
    ) {
        parent::__construct($logger);

        $this->aiService = $aiService ?? new AIService(new Logger());
        $this->analysisService = $analysisService ?? $this->createMockAnalysisService();
    }

    /**
     * Get comprehensive AI-powered inventory analysis
     */
    public function getComprehensiveAnalysis(): ?array
    {
        try {
            $options = $this->getRequestData();
            $result = $this->analysisService->getComprehensiveAnalysis($options);

            $this->logAction('ai_comprehensive_analysis', [
                'items_analyzed' => $result['items_analyzed'] ?? 0,
                'risk_level' => $result['risk_assessment'] ?? 'unknown'
            ]);

            return $this->successResponse([
                'analysis' => $result,
                'ai_service_available' => $this->aiService->isAvailable()
            ], 'Comprehensive analysis completed successfully');
        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::getComprehensiveAnalysis failed: " . $e->getMessage());
            return $this->errorResponse('Failed to generate comprehensive analysis', [], 500);
        }
    }

    /**
     * Generate weekly AI-powered report
     */
    public function generateWeeklyReport(): ?array
    {
        try {
            $report = $this->analysisService->generateWeeklyReport();

            $this->logAction('ai_weekly_report', [
                'period' => $report['period']['type'] ?? 'weekly'
            ]);

            return $this->successResponse([
                'report' => $report,
                'generated_at' => date('c')
            ], 'Weekly report generated successfully');
        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::generateWeeklyReport failed: " . $e->getMessage());
            return $this->errorResponse('Failed to generate weekly report', [], 500);
        }
    }

    /**
     * Monitor critical items dengan AI alerts
     */
    public function monitorCriticalItems(): ?array
    {
        try {
            $monitoringResult = $this->analysisService->monitorCriticalItems();

            $this->logAction('ai_critical_monitoring', [
                'total_alerts' => $monitoringResult['total_critical_items'] ?? 0,
                'risk_level' => $monitoringResult['risk_level'] ?? 'unknown'
            ]);

            return $this->successResponse([
                'monitoring' => $monitoringResult,
                'monitored_at' => date('c')
            ], 'Critical items monitoring completed');
        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::monitorCriticalItems failed: " . $e->getMessage());
            return $this->errorResponse('Failed to monitor critical items', [], 500);
        }
    }

    /**
     * Predict inventory needs dengan AI
     */
    public function predictInventoryNeeds(int $days = null): ?array
    {
        try {
            $forecastDays = $days ?? (int) $this->getRequestValue('forecast_days', 30);

            $prediction = $this->analysisService->predictInventoryNeeds($forecastDays);

            $this->logAction('ai_inventory_prediction', [
                'forecast_days' => $forecastDays,
                'confidence' => $prediction['confidence_score'] ?? 0
            ]);

            return $this->successResponse([
                'prediction' => $prediction,
                'forecast_period' => $forecastDays
            ], 'Inventory needs prediction completed');
        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::predictInventoryNeeds failed: " . $e->getMessage());
            return $this->errorResponse('Failed to predict inventory needs', [], 500);
        }
    }

    /**
     * Optimize inventory dengan AI
     */
    public function optimizeInventory(): ?array
    {
        try {
            $optimizationResult = $this->analysisService->optimizeInventory();

            $this->logAction('ai_inventory_optimization', [
                'items_optimized' => $optimizationResult['total_items_optimized'] ?? 0,
                'potential_savings' => $optimizationResult['savings_analysis']['total_potential_savings'] ?? 0
            ]);

            return $this->successResponse([
                'optimization' => $optimizationResult,
                'optimized_at' => date('c')
            ], 'Inventory optimization completed successfully');
        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::optimizeInventory failed: " . $e->getMessage());
            return $this->errorResponse('Failed to optimize inventory', [], 500);
        }
    }

    /**
     * Analyze sales trends dengan AI
     */
    public function analyzeSalesTrends(): ?array
    {
        try {
            $this->logAction('analyze_sales_trends');
            
            // Get request data
            $salesData = $this->getRequestValue('sales_data', []);
            $periodDays = (int) $this->getRequestValue('period_days', 30);
            
            // Basic validation
            if (empty($salesData)) {
                return $this->validationErrorResponse([
                    'sales_data' => 'Sales data is required and cannot be empty'
                ]);
            }
            
            if ($periodDays <= 0 || $periodDays > 365) {
                return $this->validationErrorResponse([
                    'period_days' => 'Period days must be between 1 and 365'
                ]);
            }
            
            // FIX: Call AIService with correct parameters
            // AIService::analyzeSalesTrends expects two parameters: array $salesData, int $periodDays
            $analysis = $this->aiService->analyzeSalesTrends($salesData, $periodDays);
            
            return $this->successResponse([
                'analysis' => $analysis,
                'parameters' => [
                    'data_points' => count($salesData),
                    'period_days' => $periodDays
                ]
            ], 'Sales trends analysis completed');
            
        } catch (\InvalidArgumentException $e) {
            // Handle validation errors from AIService
            return $this->validationErrorResponse([
                'sales_data' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Sales trends analysis failed: ' . $e->getMessage());
            return $this->errorResponse('Failed to analyze sales trends: ' . $e->getMessage());
        }
    }

    /**
     * Get AI service status dan capabilities
     */
    public function getAIStatus(): ?array
    {
        try {
            $status = [
                'ai_service_available' => $this->aiService->isAvailable(),
                'available_strategies' => $this->aiService->getAvailableStrategies(),
                'active_strategy' => 'advanced_analysis',
                'ml_enabled' => true,
                'capabilities' => [
                    'sales_trends_analysis',
                    'inventory_turnover_prediction',
                    'stock_optimization',
                    'risk_assessment',
                    'predictive_analytics'
                ],
                'timestamp' => date('c')
            ];

            return $this->successResponse($status, 'AI service status retrieved');
        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::getAIStatus failed: " . $e->getMessage());
            return $this->errorResponse('Failed to get AI status', [], 500);
        }
    }

    /**
     * Create mock analysis service untuk testing
     */
    private function createMockAnalysisService(): InventoryAnalysisService
    {
        return new class($this->aiService, new Logger()) extends InventoryAnalysisService {
            public function getComprehensiveAnalysis(array $options = []): array {
                return [
                    'summary' => ['status' => 'mock_analysis'],
                    'risk_assessment' => 'low',
                    'ai_insights' => ['Mock analysis for testing'],
                    'analysis_timestamp' => date('c'),
                    'items_analyzed' => 1
                ];
            }
            public function generateWeeklyReport(): array {
                return [
                    'period' => ['type' => 'weekly'],
                    'executive_summary' => ['Mock weekly report'],
                    'generated_at' => date('c')
                ];
            }
            public function monitorCriticalItems(): array {
                return [
                    'alerts' => [],
                    'risk_level' => 'low',
                    'total_critical_items' => 0
                ];
            }
            public function predictInventoryNeeds(int $forecastDays = 30): array {
                return [
                    'forecast_period' => $forecastDays,
                    'prediction_summary' => ['Mock prediction'],
                    'confidence_score' => 0.8
                ];
            }
            public function optimizeInventory(): array {
                return [
                    'optimization_results' => [],
                    'total_items_optimized' => 0
                ];
            }
        };
    }
}
