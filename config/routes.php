<?php
declare(strict_types=1);

use App\Controller\AIAnalysisController;
use App\Utility\Router;

/**
 * Route configuration for the application
 * This file defines all API endpoints and their handlers
 */
return function(Router $router) {
    
    // AI Analysis Routes - Protected endpoints for inventory intelligence
    $router->group('/ai', function($router) {
        
        // Comprehensive AI Analysis
        $router->get('/analysis', [AIAnalysisController::class, 'getComprehensiveAnalysis']);
        
        // Report Generation
        $router->get('/report/weekly', [AIAnalysisController::class, 'generateWeeklyReport']);
        
        // Monitoring & Alerts
        $router->get('/monitor/critical', [AIAnalysisController::class, 'monitorCriticalItems']);
        
        // Predictive Analytics
        $router->get('/predict/{days}', [AIAnalysisController::class, 'predictInventoryNeeds']);
        
        // Optimization
        $router->post('/optimize', [AIAnalysisController::class, 'optimizeInventory']);
        
        // Sales Analysis
        $router->post('/analyze/sales-trends', [AIAnalysisController::class, 'analyzeSalesTrends']);
        
        // System Status
        $router->get('/status', [AIAnalysisController::class, 'getAIStatus']);
        
    });

    // Health check endpoint (public)
    $router->get('/health', function() {
        return [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'service' => 'inventory-ai-api',
            'version' => '1.0.0'
        ];
    });

    // API documentation endpoint
    $router->get('/api/docs', function() {
        return [
            'api' => 'Inventory AI API',
            'version' => '1.0.0',
            'endpoints' => [
                'ai_analysis' => [
                    'GET /ai/analysis' => 'Comprehensive inventory analysis',
                    'GET /ai/report/weekly' => 'Generate weekly report',
                    'GET /ai/monitor/critical' => 'Monitor critical items',
                    'GET /ai/predict/{days}' => 'Predict inventory needs',
                    'POST /ai/optimize' => 'Optimize inventory levels',
                    'POST /ai/analyze/sales-trends' => 'Analyze sales trends',
                    'GET /ai/status' => 'Get AI service status'
                ],
                'system' => [
                    'GET /health' => 'Health check',
                    'GET /api/docs' => 'API documentation'
                ]
            ],
            'authentication' => 'Bearer Token (JWT) required for AI endpoints',
            'rate_limiting' => '100 requests per hour per user'
        ];
    });

    // 404 Handler for undefined routes
    $router->setNotFoundHandler(function() {
        // http_response_code(404);
        return [
            'status' => 'error',
            'message' => 'Endpoint not found',
            'timestamp' => time(),
            'documentation' => '/api/docs'
        ];
    });
};