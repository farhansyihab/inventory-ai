<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\Reporting\IReportingService;
use App\Model\Report\ReportDefinition;
use App\Model\Report\DateRangeFilter;
use App\Utility\Logger;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ReportingController - Controller untuk handle semua endpoint reporting
 * Mengikuti pola yang konsisten dengan DashboardController
 */
class ReportingController extends BaseController
{
    private IReportingService $reportingService;

    public function __construct(IReportingService $reportingService, Logger $logger = null)
    {
        parent::__construct($logger);
        $this->reportingService = $reportingService;
    }

    /**
     * GET /reports/inventory - Basic inventory report
     */
    public function getInventoryReport(Request $request): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            $filters = $this->buildFiltersFromRequest($request);
            $dateRange = $this->buildDateRangeFromRequest($request);
            
            $definition = ReportDefinition::createSimple(
                'inventory',
                'Inventory Report',
                $dateRange,
                $filters
            );

            $result = $this->reportingService->generateReport($definition);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return $this->buildReportResponse($result, $responseTime);

        } catch (\Exception $e) {
            $this->logger->error('Inventory report endpoint failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse(
                'Failed to generate inventory report: ' . $e->getMessage(),
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * POST /reports/inventory/advanced - Advanced inventory analytics
     */
    public function getAdvancedInventoryReport(Request $request): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            $requestData = $this->getRequestData($request);
            $definition = $this->buildReportDefinitionFromRequest($requestData, 'inventory');
            
            $result = $this->reportingService->generateReport($definition);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return $this->buildReportResponse($result, $responseTime);

        } catch (\Exception $e) {
            $this->logger->error('Advanced inventory report endpoint failed', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to generate advanced inventory report: ' . $e->getMessage(),
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * GET /reports/users/activity - User activity report
     */
    public function getUserActivityReport(Request $request): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            $dateRange = $this->buildDateRangeFromRequest($request);
            $definition = ReportDefinition::createSimple(
                'user_activity',
                'User Activity Report',
                $dateRange
            );

            $result = $this->reportingService->generateReport($definition);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return $this->buildReportResponse($result, $responseTime);

        } catch (\Exception $e) {
            $this->logger->error('User activity report endpoint failed', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to generate user activity report: ' . $e->getMessage(),
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * GET /reports/ai/performance - AI performance report
     */
    public function getAIPerformanceReport(Request $request): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            $definition = ReportDefinition::createSimple(
                'ai_performance',
                'AI Performance Report'
            );

            $result = $this->reportingService->generateReport($definition);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return $this->buildReportResponse($result, $responseTime);

        } catch (\Exception $e) {
            $this->logger->error('AI performance report endpoint failed', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to generate AI performance report: ' . $e->getMessage(),
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * GET /reports/system/audit - System audit report
     */
    public function getSystemAuditReport(Request $request): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            $dateRange = $this->buildDateRangeFromRequest($request);
            $definition = ReportDefinition::createSimple(
                'system_audit',
                'System Audit Report',
                $dateRange
            );

            $result = $this->reportingService->generateReport($definition);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            return $this->buildReportResponse($result, $responseTime);

        } catch (\Exception $e) {
            $this->logger->error('System audit report endpoint failed', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to generate system audit report: ' . $e->getMessage(),
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * POST /reports/custom - Create custom report definition
     */
    public function createCustomReport(Request $request): JsonResponse
    {
        try {
            $requestData = $this->getRequestData($request);
            
            // Validasi required fields
            $validation = $this->validateCustomReportData($requestData);
            if (!$validation['valid']) {
                return $this->validationErrorResponse($validation['errors']);
            }

            $definition = ReportDefinition::fromArray($requestData);
            
            // Test report generation untuk validasi
            $testResult = $this->reportingService->testReportGeneration($definition);
            
            if (!$testResult->isSuccess()) {
                return $this->errorResponse(
                    'Custom report validation failed: ' . $testResult->getErrorMessage(),
                    [],
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Dalam implementasi real, ini akan menyimpan definition ke database
            $savedDefinition = $this->simulateSaveDefinition($definition);

            $this->logger->info('Custom report definition created', [
                'reportId' => $savedDefinition->getId(),
                'reportType' => $savedDefinition->getType()
            ]);

            return $this->successResponse([
                'message' => 'Custom report definition created successfully',
                'reportDefinition' => $savedDefinition->toArray(),
                'testResult' => $testResult->toArray()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Custom report creation failed', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to create custom report: ' . $e->getMessage(),
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * GET /reports/available-types - Get available report types
     */
    public function getAvailableReportTypes(Request $request): JsonResponse
    {
        try {
            $reportTypes = $this->reportingService->getAvailableReportTypes();

            return $this->successResponse([
                'reportTypes' => $reportTypes,
                'count' => count($reportTypes)
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get available report types', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to get available report types: ' . $e->getMessage(),
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * GET /reports/cache/stats - Get reporting cache statistics
     */
    public function getCacheStats(Request $request): JsonResponse
    {
        try {
            $cacheStats = $this->reportingService->getCacheStats();

            return $this->successResponse([
                'cacheStats' => $cacheStats
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to get cache stats', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to get cache stats: ' . $e->getMessage(),
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * POST /reports/cache/clear - Clear reporting cache
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $this->reportingService->clearCache();

            $this->logger->info('Reporting cache cleared via API');

            return $this->successResponse([
                'message' => 'Reporting cache cleared successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to clear reporting cache', [
                'error' => $e->getMessage()
            ]);

            return $this->errorResponse(
                'Failed to clear reporting cache: ' . $e->getMessage(),
                [],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Build standardized report response
     */
    private function buildReportResponse(ReportResult $result, float $responseTime): JsonResponse
    {
        $responseData = [
            'success' => $result->isSuccess(),
            'data' => $result->toArray(),
            'meta' => [
                'responseTime' => $responseTime . 'ms',
                'generatedAt' => $result->getGeneratedAt()->format(\DateTimeInterface::ATOM),
                'recordCount' => $result->getRecordCount(),
                'executionTime' => $result->getExecutionTime() . 'ms'
            ]
        ];

        if (!$result->isSuccess()) {
            return new JsonResponse($responseData, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse($responseData, Response::HTTP_OK);
    }

    /**
     * Build filters dari request parameters
     */
    private function buildFiltersFromRequest(Request $request): array
    {
        $filters = [];

        // Category filter
        if ($category = $request->query->get('category')) {
            $filters['category'] = $category;
        }

        // Stock level filter
        if ($stockLevel = $request->query->get('stockLevel')) {
            $filters['stockLevel'] = $stockLevel;
        }

        // Search filter
        if ($search = $request->query->get('search')) {
            $filters['search'] = $search;
        }

        return $filters;
    }

    /**
     * Build date range dari request parameters
     */
    private function buildDateRangeFromRequest(Request $request): ?DateRangeFilter
    {
        $startDate = $request->query->get('startDate');
        $endDate = $request->query->get('endDate');
        $period = $request->query->get('period');

        if ($period) {
            return $this->createDateRangeFromPeriod($period);
        }

        if ($startDate && $endDate) {
            try {
                $start = new \DateTime($startDate);
                $end = new \DateTime($endDate);
                return new DateRangeFilter($start, $end);
            } catch (\Exception $e) {
                $this->logger->warning('Invalid date range provided, using default', [
                    'startDate' => $startDate,
                    'endDate' => $endDate
                ]);
            }
        }

        // Default: last 30 days
        return DateRangeFilter::createLast30Days();
    }

    /**
     * Create date range dari period string
     */
    private function createDateRangeFromPeriod(string $period): ?DateRangeFilter
    {
        return match ($period) {
            'today' => DateRangeFilter::today(),
            'yesterday' => DateRangeFilter::yesterday(),
            'this_week' => DateRangeFilter::thisWeek(),
            'last_week' => DateRangeFilter::lastWeek(),
            'this_month' => DateRangeFilter::thisMonth(),
            'last_month' => DateRangeFilter::lastMonth(),
            'last_7_days' => DateRangeFilter::createLast7Days(),
            'last_30_days' => DateRangeFilter::createLast30Days(),
            default => DateRangeFilter::createLast30Days()
        };
    }

    /**
     * Build report definition dari request data
     */
    private function buildReportDefinitionFromRequest(array $requestData, string $defaultType = 'custom'): ReportDefinition
    {
        $type = $requestData['type'] ?? $defaultType;
        $name = $requestData['name'] ?? ucfirst($type) . ' Report';
        
        $definition = new ReportDefinition(
            $type,
            $name,
            $requestData['description'] ?? '',
            $requestData['filters'] ?? [],
            $requestData['columns'] ?? [],
            $requestData['sorting'] ?? [],
            $this->buildDateRangeFromArray($requestData['dateRange'] ?? []),
            $requestData['createdBy'] ?? 'api_user',
            null, // id
            null, // createdAt
            null, // updatedAt
            $requestData['metadata'] ?? []
        );

        return $definition;
    }

    /**
     * Build date range dari array data
     */
    private function buildDateRangeFromArray(array $dateRangeData): ?DateRangeFilter
    {
        if (empty($dateRangeData) || empty($dateRangeData['startDate']) || empty($dateRangeData['endDate'])) {
            return null;
        }

        try {
            $start = new \DateTime($dateRangeData['startDate']);
            $end = new \DateTime($dateRangeData['endDate']);
            $timezone = $dateRangeData['timezone'] ?? 'UTC';

            return new DateRangeFilter($start, $end, $timezone);
        } catch (\Exception $e) {
            $this->logger->warning('Invalid date range data', [
                'dateRangeData' => $dateRangeData,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Validasi custom report data
     */
    private function validateCustomReportData(array $data): array
    {
        $errors = [];

        if (empty($data['type'])) {
            $errors[] = 'Report type is required';
        }

        if (empty($data['name'])) {
            $errors[] = 'Report name is required';
        }

        if (!empty($data['name']) && strlen($data['name']) > 255) {
            $errors[] = 'Report name cannot exceed 255 characters';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Simulate saving report definition (stub untuk Phase 3.2.1)
     */
    private function simulateSaveDefinition(ReportDefinition $definition): ReportDefinition
    {
        // Dalam Phase 3.2.2, ini akan menyimpan ke database
        // Untuk sekarang, kita hanya set ID dan return
        $reflection = new \ReflectionClass($definition);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($definition, 'cust_' . uniqid());

        return $definition;
    }

    /**
     * Get request data dengan support untuk JSON body
     */
    private function getRequestData(Request $request): array
    {
        if ($request->getContentType() === 'json') {
            $data = json_decode($request->getContent(), true);
            return is_array($data) ? $data : [];
        }

        return array_merge($request->query->all(), $request->request->all());
    }
}