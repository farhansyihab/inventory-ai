<?php
// File: src/Controller/AIAnalysisController.php
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
        
        // Dependency injection dengan defaults
        $this->aiService = $aiService ?? new AIService(new Logger());
        $this->analysisService = $analysisService ?? new InventoryAnalysisService(
            $this->aiService,
            new InventoryService(new InventoryRepository(), new Logger()),
            new Logger()
        );
    }

    /**
     * Get comprehensive AI-powered inventory analysis
     */
    public function getComprehensiveAnalysis(): void
    {
        try {
            $options = $this->getRequestData();
            
            $result = $this->analysisService->getComprehensiveAnalysis($options);

            $this->logAction('ai_comprehensive_analysis', [
                'items_analyzed' => $result['items_analyzed'] ?? 0,
                'risk_level' => $result['risk_assessment'] ?? 'unknown'
            ]);

            $this->successResponse([
                'analysis' => $result,
                'ai_service_available' => $this->aiService->isAvailable()
            ], 'Comprehensive analysis completed successfully');

        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::getComprehensiveAnalysis failed: " . $e->getMessage());
            $this->errorResponse('Failed to generate comprehensive analysis', [], 500);
        }
    }

    /**
     * Generate weekly AI-powered report
     */
    public function generateWeeklyReport(): void
    {
        try {
            $report = $this->analysisService->generateWeeklyReport();

            $this->logAction('ai_weekly_report', [
                'period' => $report['period']['type'] ?? 'weekly'
            ]);

            $this->successResponse([
                'report' => $report,
                'generated_at' => date('c')
            ], 'Weekly report generated successfully');

        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::generateWeeklyReport failed: " . $e->getMessage());
            $this->errorResponse('Failed to generate weekly report', [], 500);
        }
    }

    /**
     * Monitor critical items dengan AI alerts
     */
    public function monitorCriticalItems(): void
    {
        try {
            $monitoringResult = $this->analysisService->monitorCriticalItems();

            $this->logAction('ai_critical_monitoring', [
                'total_alerts' => $monitoringResult['total_critical_items'] ?? 0,
                'risk_level' => $monitoringResult['risk_level'] ?? 'unknown'
            ]);

            $this->successResponse([
                'monitoring' => $monitoringResult,
                'monitored_at' => date('c')
            ], 'Critical items monitoring completed');

        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::monitorCriticalItems failed: " . $e->getMessage());
            $this->errorResponse('Failed to monitor critical items', [], 500);
        }
    }

    /**
     * Predict inventory needs dengan AI
     */
    public function predictInventoryNeeds(): void
    {
        try {
            $requestData = $this->getRequestData();
            $forecastDays = (int) ($requestData['forecast_days'] ?? 30);

            $prediction = $this->analysisService->predictInventoryNeeds($forecastDays);

            $this->logAction('ai_inventory_prediction', [
                'forecast_days' => $forecastDays,
                'confidence' => $prediction['confidence_score'] ?? 0
            ]);

            $this->successResponse([
                'prediction' => $prediction,
                'forecast_period' => $forecastDays
            ], 'Inventory needs prediction completed');

        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::predictInventoryNeeds failed: " . $e->getMessage());
            $this->errorResponse('Failed to predict inventory needs', [], 500);
        }
    }

    /**
     * Optimize inventory dengan AI
     */
    public function optimizeInventory(): void
    {
        try {
            $optimizationResult = $this->analysisService->optimizeInventory();

            $this->logAction('ai_inventory_optimization', [
                'items_optimized' => $optimizationResult['total_items_optimized'] ?? 0,
                'potential_savings' => $optimizationResult['savings_analysis']['total_potential_savings'] ?? 0
            ]);

            $this->successResponse([
                'optimization' => $optimizationResult,
                'optimized_at' => date('c')
            ], 'Inventory optimization completed successfully');

        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::optimizeInventory failed: " . $e->getMessage());
            $this->errorResponse('Failed to optimize inventory', [], 500);
        }
    }

    /**
     * Analyze sales trends dengan AI
     */
    public function analyzeSalesTrends(): void
    {
        try {
            $requestData = $this->getRequestData();
            $salesData = $requestData['sales_data'] ?? [];
            $periodDays = (int) ($requestData['period_days'] ?? 30);

            if (empty($salesData)) {
                $this->validationErrorResponse(['sales_data' => 'Sales data is required']);
                return;
            }

            $analysis = $this->aiService->analyzeSalesTrends($salesData, $periodDays);

            $this->logAction('ai_sales_trends_analysis', [
                'data_points' => count($salesData),
                'period_days' => $periodDays
            ]);

            $this->successResponse([
                'analysis' => $analysis,
                'period_analyzed' => $periodDays
            ], 'Sales trends analysis completed');

        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::analyzeSalesTrends failed: " . $e->getMessage());
            $this->errorResponse('Failed to analyze sales trends', [], 500);
        }
    }

    /**
     * Get AI service status dan capabilities
     */
    public function getAIStatus(): void
    {
        try {
            $status = [
                'ai_service_available' => $this->aiService->isAvailable(),
                'available_strategies' => $this->aiService->getAvailableStrategies(),
                'active_strategy' => 'advanced_analysis', // Default strategy
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

            $this->successResponse($status, 'AI service status retrieved');

        } catch (\Exception $e) {
            $this->logger->error("AIAnalysisController::getAIStatus failed: " . $e->getMessage());
            $this->errorResponse('Failed to get AI status', [], 500);
        }
    }
}