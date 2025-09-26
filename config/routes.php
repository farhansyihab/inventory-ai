<?php
declare(strict_types=1);

use App\Controller\AIAnalysisController;
use App\Controller\CategoryController;
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

    // Category Management Routes - Comprehensive category API
    $router->group('/categories', function($router) {
        
        // Public endpoints (read-only access)
        $router->get('/', [CategoryController::class, 'listCategories']);
        $router->get('/tree', [CategoryController::class, 'getCategoryTree']);
        $router->get('/roots', [CategoryController::class, 'getRootCategories']);
        $router->get('/statistics', [CategoryController::class, 'getStatistics']);
        $router->get('/search', [CategoryController::class, 'searchCategories']);
        
        // Category by identifier endpoints
        $router->get('/{id}', [CategoryController::class, 'getCategory']);
        $router->get('/slug/{slug}', [CategoryController::class, 'getCategoryBySlug']);
        
        // Hierarchical endpoints
        $router->get('/{id}/subcategories', [CategoryController::class, 'getSubcategories']);
        $router->get('/{id}/path', [CategoryController::class, 'getCategoryPath']);
        
        // Protected endpoints (require authentication - will be added when middleware is ready)
        $router->post('/', [CategoryController::class, 'createCategory']);
        $router->put('/{id}', [CategoryController::class, 'updateCategory']);
        $router->delete('/{id}', [CategoryController::class, 'deleteCategory']);
        $router->post('/{id}/move', [CategoryController::class, 'moveCategory']);
        $router->patch('/bulk-status', [CategoryController::class, 'bulkUpdateStatus']);
        
    });

    // Health check endpoint (public)
    $router->get('/health', function() {
        return [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'service' => 'inventory-ai-api',
            'version' => '1.0.0',
            'modules' => [
                'ai_analysis' => 'active',
                'category_management' => 'active',
                'authentication' => 'pending'
            ]
        ];
    });

    // API documentation endpoint
    $router->get('/api/docs', function() {
        return [
            'api' => 'Inventory AI API',
            'version' => '1.0.0',
            'endpoints' => [
                'category_management' => [
                    'GET /categories' => 'List all categories with pagination',
                    'GET /categories/tree' => 'Get complete category hierarchy tree',
                    'GET /categories/roots' => 'Get root categories',
                    'GET /categories/statistics' => 'Get category statistics',
                    'GET /categories/search?q=query' => 'Search categories by name or slug',
                    'GET /categories/{id}' => 'Get category by ID',
                    'GET /categories/slug/{slug}' => 'Get category by slug',
                    'GET /categories/{id}/subcategories' => 'Get subcategories of a category',
                    'GET /categories/{id}/path' => 'Get category path (ancestors)',
                    'POST /categories' => 'Create new category (protected)',
                    'PUT /categories/{id}' => 'Update category (protected)',
                    'DELETE /categories/{id}' => 'Delete category (protected)',
                    'POST /categories/{id}/move' => 'Move category to new parent (protected)',
                    'PATCH /categories/bulk-status' => 'Bulk update category status (protected)'
                ],
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
            'authentication' => 'Bearer Token (JWT) required for protected endpoints',
            'rate_limiting' => '100 requests per hour per user',
            'pagination' => 'Use ?page=1&limit=20 for paginated endpoints',
            'filtering' => 'Use ?active=true, ?parentId=id, ?depth=number for filtering',
            'sorting' => 'Use ?sort=field:asc|desc for sorting'
        ];
    });

    // Category-specific health check
    $router->get('/health/categories', function() {
        try {
            // Simple check to verify category functionality
            $mongoManager = App\Config\MongoDBManager::getInstance();
            $collection = $mongoManager->getCollection('categories');
            $count = $collection->countDocuments();
            
            return [
                'status' => 'healthy',
                'database' => 'connected',
                'categories_count' => $count,
                'timestamp' => date('c')
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'database' => 'disconnected',
                'error' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    });

    // 404 Handler for undefined routes
    $router->setNotFoundHandler(function() {
        return [
            'status' => 'error',
            'message' => 'Endpoint not found',
            'timestamp' => time(),
            'documentation' => '/api/docs',
            'available_endpoints' => [
                '/categories',
                '/categories/tree',
                '/categories/statistics',
                '/ai/analysis',
                '/health',
                '/api/docs'
            ]
        ];
    });
};