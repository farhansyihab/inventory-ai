<?php
// src/Controller/DashboardController.php

namespace App\Controller;

use App\Service\DashboardService;
use App\Model\DashboardMetrics;
use App\Exception\DashboardException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController
{
    private DashboardService $dashboardService;
    private LoggerInterface $logger;

    public function __construct(DashboardService $dashboardService, LoggerInterface $logger)
    {
        $this->dashboardService = $dashboardService;
        $this->logger = $logger;
    }

    public function getMetrics(Request $request): JsonResponse
    {
        try {
            $startTime = microtime(true);
            
            $forceRefresh = filter_var($request->query->get('refresh', false), FILTER_VALIDATE_BOOLEAN);
            $detailed = filter_var($request->query->get('detailed', false), FILTER_VALIDATE_BOOLEAN);
            $format = $request->query->get('format', 'json');

            $this->logger->info('Dashboard metrics request received', [
                'forceRefresh' => $forceRefresh,
                'detailed' => $detailed,
                'format' => $format
            ]);

            $metrics = $this->dashboardService->getDashboardMetrics($forceRefresh, $detailed);

            $responseData = [
                'success' => true,
                'data' => $metrics->toArray(),
                'meta' => [
                    'generatedAt' => $metrics->getGeneratedAt()->format(\DateTime::ATOM),
                    'cache' => $forceRefresh ? 'bypassed' : 'used',
                    'responseTime' => round((microtime(true) - $startTime) * 1000, 2) . 'ms'
                ]
            ];

            $this->logger->info('Dashboard metrics delivered successfully', [
                'responseTime' => $responseData['meta']['responseTime'],
                'summary' => $metrics->getSummary()
            ]);

            return new JsonResponse($responseData, Response::HTTP_OK);

        } catch (DashboardException $e) {
            $this->logger->error('Dashboard metrics request failed', [
                'error' => $e->getMessage(),
                'code' => $e->getErrorCode(),
                'context' => $e->getContext()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => $e->getErrorCode(),
                    'message' => $e->getMessage(),
                    'details' => $e->getContext()
                ]
            ], $this->getHttpStatusCode($e));
        } catch (\Exception $e) {
            $this->logger->critical('Unexpected error in dashboard controller', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => [
                    'code' => 'INTERNAL_ERROR',
                    'message' => 'An unexpected error occurred'
                ]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getHealth(Request $request): JsonResponse
    {
        try {
            $metrics = $this->dashboardService->getDashboardMetrics(false, false);
            
            $healthStatus = 'healthy';
            $alerts = $metrics->getAlerts();
            
            foreach ($alerts as $alert) {
                if ($alert['level'] === 'critical') {
                    $healthStatus = 'unhealthy';
                    break;
                } elseif ($alert['level'] === 'warning') {
                    $healthStatus = 'degraded';
                }
            }

            return new JsonResponse([
                'status' => $healthStatus,
                'timestamp' => $metrics->getGeneratedAt()->format(\DateTime::ATOM),
                'alerts' => count($alerts),
                'details' => [
                    'inventory' => $metrics->getInventory()['healthStatus'] ?? 'unknown',
                    'system' => $metrics->getSystem()['health']['status'] ?? 'unknown'
                ]
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'unhealthy',
                'timestamp' => (new \DateTime())->format(\DateTime::ATOM),
                'error' => $e->getMessage()
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    public function clearCache(Request $request): JsonResponse
    {
        try {
            $this->dashboardService->clearCache();
            
            $this->logger->info('Dashboard cache cleared via API');
            
            return new JsonResponse([
                'success' => true,
                'message' => 'Dashboard cache cleared successfully'
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to clear dashboard cache', [
                'error' => $e->getMessage()
            ]);

            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getCacheStats(Request $request): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getCacheStats();
            
            return new JsonResponse([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getHttpStatusCode(DashboardException $e): int
    {
        return match ($e->getErrorCode()) {
            DashboardException::SERVICE_UNAVAILABLE => Response::HTTP_SERVICE_UNAVAILABLE,
            DashboardException::INVALID_DATA => Response::HTTP_BAD_REQUEST,
            DashboardException::CACHE_ERROR => Response::HTTP_INTERNAL_SERVER_ERROR,
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }
}