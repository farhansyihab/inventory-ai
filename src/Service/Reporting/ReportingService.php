<?php
declare(strict_types=1);

namespace App\Service\Reporting;

use App\Model\Report\ReportDefinition;
use App\Model\Report\ReportResult;
use App\Model\Report\ReportSchedule;
use App\Model\Report\ExportJob;
use App\Service\Reporting\Builders\InventoryReportBuilder;
use App\Service\Reporting\Builders\UserReportBuilder;
use App\Service\Reporting\Builders\AIReportBuilder;
use App\Service\Reporting\Builders\SystemReportBuilder;
use App\Service\Reporting\Exporters\ReportExporter;
use App\Service\Reporting\Schedulers\ReportScheduler;
use App\Service\Reporting\Analytics\ReportAnalyzer;
use App\Service\IInventoryService;
use App\Service\UserService;
use App\Service\AIService;
use App\Service\DashboardService;
use Psr\Log\LoggerInterface;
use App\Utility\Logger;

/**
 * ReportingService - Core service untuk handle semua operasi reporting
 * Mengikuti pola yang konsisten dengan DashboardService
 */
class ReportingService implements IReportingService
{
    private array $builders;
    private ReportExporter $exporter;
    private ReportScheduler $scheduler;
    private ReportAnalyzer $analyzer;
    private LoggerInterface $logger;
    private array $cache;
    private int $cacheTtl;

    public function __construct(
        IInventoryService $inventoryService,
        UserService $userService,
        AIService $aiService,
        DashboardService $dashboardService,
        LoggerInterface $logger = null,
        int $cacheTtl = 300
    ) {
        $this->logger = $logger ?? Logger::getLogger();
        $this->cacheTtl = $cacheTtl;
        $this->cache = [];

        // Initialize builders
        $this->initializeBuilders($inventoryService, $userService, $aiService, $dashboardService);
        
        // Initialize components
        $this->exporter = new ReportExporter($this->logger);
        $this->scheduler = new ReportScheduler($this->logger);
        $this->analyzer = new ReportAnalyzer($this->logger);

        $this->logger->info('ReportingService initialized', [
            'builders' => array_keys($this->builders),
            'cacheTtl' => $this->cacheTtl
        ]);
    }

    /**
     * Initialize report builders berdasarkan dependencies
     */
    private function initializeBuilders(
        IInventoryService $inventoryService,
        UserService $userService,
        AIService $aiService,
        DashboardService $dashboardService
    ): void {
        $this->builders = [
            'inventory' => new InventoryReportBuilder($inventoryService, $aiService, $this->logger),
            'user_activity' => new UserReportBuilder($userService, $this->logger),
            'ai_performance' => new AIReportBuilder($aiService, $dashboardService, $this->logger),
            'system_audit' => new SystemReportBuilder($dashboardService, $this->logger)
        ];
    }

    public function generateReport(ReportDefinition $definition): ReportResult
    {
        $startTime = microtime(true);
        $cacheKey = $this->generateCacheKey($definition);

        $this->logger->info('Starting report generation', [
            'reportId' => $definition->getId(),
            'reportType' => $definition->getType(),
            'reportName' => $definition->getName()
        ]);

        try {
            // Check cache terlebih dahulu
            if ($this->isCacheValid($cacheKey)) {
                $this->logger->info('Returning cached report result', ['cacheKey' => $cacheKey]);
                $this->scheduler->recordCacheHit();
                return $this->cache[$cacheKey]['data'];
            }

            // Validasi definition
            $validationResult = $this->validateReportDefinition($definition);
            if (!$validationResult['valid']) {
                throw new \InvalidArgumentException(
                    'Report definition validation failed: ' . implode(', ', $validationResult['errors'])
                );
            }

            // Get appropriate builder
            $builder = $this->getBuilderForType($definition->getType());
            
            // Generate report
            $result = $builder->buildReport($definition);
            
            // Calculate execution time
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            // Update metadata
            $result->getMetadata()->setExecutionTime($executionTime);
            $result->getMetadata()->setStatus('success');

            // Cache the result
            $this->cacheResult($cacheKey, $result);

            $this->logger->info('Report generated successfully', [
                'reportId' => $definition->getId(),
                'executionTime' => $executionTime,
                'recordCount' => $result->getRecordCount()
            ]);

            return $result;

        } catch (\Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logger->error('Report generation failed', [
                'reportId' => $definition->getId(),
                'error' => $e->getMessage(),
                'executionTime' => $executionTime
            ]);

            return ReportResult::createError($definition, $e->getMessage(), $executionTime);
        }
    }

    public function generateRealTimeReport(string $type, array $filters = []): ReportResult
    {
        $this->logger->info('Generating real-time report', [
            'type' => $type,
            'filters' => $filters
        ]);

        try {
            $builder = $this->getBuilderForType($type);
            $definition = ReportDefinition::createSimple($type, "Real-time {$type} Report", null, $filters);
            
            return $builder->buildRealTimeReport($definition);

        } catch (\Exception $e) {
            $this->logger->error('Real-time report generation failed', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            $definition = ReportDefinition::createSimple($type, "Real-time {$type} Report");
            return ReportResult::createError($definition, $e->getMessage());
        }
    }

    public function scheduleReport(ReportSchedule $schedule): string
    {
        return $this->scheduler->schedule($schedule);
    }

    public function cancelSchedule(string $scheduleId): bool
    {
        return $this->scheduler->cancel($scheduleId);
    }

    public function getReportHistory(string $reportType, DateRange $range): array
    {
        $this->logger->info('Fetching report history', [
            'reportType' => $reportType,
            'range' => $range->toArray()
        ]);

        // Ini akan diimplementasi dengan storage yang proper nanti
        // Untuk sekarang return empty array
        return [];
    }

    public function exportReport(ReportResult $result, string $format): ExportJob
    {
        return $this->exporter->export($result, $format);
    }

    public function getExportStatus(string $jobId): ExportJob
    {
        return $this->exporter->getStatus($jobId);
    }

    public function analyzeReportTrends(ReportResult $current, ReportResult $previous): array
    {
        return $this->analyzer->analyzeTrends($current, $previous);
    }

    public function generatePredictiveReport(string $type, int $forecastPeriod): ReportResult
    {
        $this->logger->info('Generating predictive report', [
            'type' => $type,
            'forecastPeriod' => $forecastPeriod
        ]);

        try {
            $builder = $this->getBuilderForType($type);
            $definition = ReportDefinition::createSimple($type, "Predictive {$type} Report");
            
            return $builder->buildPredictiveReport($definition, $forecastPeriod);

        } catch (\Exception $e) {
            $this->logger->error('Predictive report generation failed', [
                'type' => $type,
                'error' => $e->getMessage()
            ]);

            $definition = ReportDefinition::createSimple($type, "Predictive {$type} Report");
            return ReportResult::createError($definition, $e->getMessage());
        }
    }

    public function getAvailableReportTypes(): array
    {
        return [
            'inventory' => [
                'name' => 'Inventory Report',
                'description' => 'Laporan stok inventory dengan analisis kesehatan stok',
                'supportedFormats' => ['json', 'csv', 'pdf'],
                'requiresDateRange' => true
            ],
            'user_activity' => [
                'name' => 'User Activity Report',
                'description' => 'Laporan aktivitas pengguna dan usage statistics',
                'supportedFormats' => ['json', 'csv'],
                'requiresDateRange' => true
            ],
            'ai_performance' => [
                'name' => 'AI Performance Report',
                'description' => 'Laporan performa model AI dan analisis accuracy',
                'supportedFormats' => ['json', 'csv'],
                'requiresDateRange' => false
            ],
            'system_audit' => [
                'name' => 'System Audit Report',
                'description' => 'Laporan audit sistem dan security events',
                'supportedFormats' => ['json', 'csv', 'pdf'],
                'requiresDateRange' => true
            ]
        ];
    }

    public function validateReportDefinition(ReportDefinition $definition): array
    {
        $errors = [];

        try {
            $definition->validate();
        } catch (\InvalidArgumentException $e) {
            $errors[] = $e->getMessage();
        }

        // Validasi tambahan: check jika report type supported
        $availableTypes = array_keys($this->getAvailableReportTypes());
        if (!in_array($definition->getType(), $availableTypes)) {
            $errors[] = "Report type '{$definition->getType()}' is not supported";
        }

        // Validasi date range untuk report types yang membutuhkan
        $reportTypes = $this->getAvailableReportTypes();
        if (isset($reportTypes[$definition->getType()]['requiresDateRange']) &&
            $reportTypes[$definition->getType()]['requiresDateRange'] &&
            $definition->getDateRange() === null) {
            $errors[] = "Report type '{$definition->getType()}' requires a date range";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    public function testReportGeneration(ReportDefinition $definition): ReportResult
    {
        $this->logger->info('Testing report generation', [
            'reportType' => $definition->getType(),
            'reportName' => $definition->getName()
        ]);

        // Untuk test, kita batasi record count dan gunakan sample data
        $testDefinition = clone $definition;
        
        // Add test metadata
        $testDefinition->setMetadata(array_merge(
            $testDefinition->getMetadata(),
            ['test_mode' => true, 'max_records' => 10]
        ));

        return $this->generateReport($testDefinition);
    }

    /**
     * Get builder untuk report type tertentu
     */
    private function getBuilderForType(string $type): object
    {
        if (!isset($this->builders[$type])) {
            throw new \InvalidArgumentException("No builder available for report type: {$type}");
        }

        return $this->builders[$type];
    }

    /**
     * Generate cache key berdasarkan report definition
     */
    private function generateCacheKey(ReportDefinition $definition): string
    {
        $keyData = [
            'type' => $definition->getType(),
            'filters' => $definition->getFilters(),
            'columns' => $definition->getColumns(),
            'dateRange' => $definition->getDateRange()?->toArray()
        ];

        return 'report_' . md5(serialize($keyData));
    }

    /**
     * Check jika cache masih valid
     */
    private function isCacheValid(string $cacheKey): bool
    {
        if (!isset($this->cache[$cacheKey])) {
            return false;
        }

        $cacheTime = $this->cache[$cacheKey]['timestamp'];
        return (time() - $cacheTime) < $this->cacheTtl;
    }

    /**
     * Cache report result
     */
    private function cacheResult(string $cacheKey, ReportResult $result): void
    {
        $this->cache[$cacheKey] = [
            'data' => $result,
            'timestamp' => time()
        ];

        // Cleanup old cache entries
        $this->cleanupCache();
    }

    /**
     * Cleanup cache yang expired
     */
    private function cleanupCache(): void
    {
        $maxEntries = 50; // Maximum cache entries
        if (count($this->cache) > $maxEntries) {
            // Remove oldest entries
            uasort($this->cache, function($a, $b) {
                return $a['timestamp'] <=> $b['timestamp'];
            });
            $this->cache = array_slice($this->cache, -$maxEntries, $maxEntries, true);
        }
    }

    /**
     * Get cache statistics untuk monitoring
     */
    public function getCacheStats(): array
    {
        $hits = 0;
        $misses = 0;

        // Simple hit/miss tracking (dalam implementasi real akan lebih sophisticated)
        foreach ($this->cache as $entry) {
            // Basic tracking - bisa diperluas nanti
            $hits++;
        }

        return [
            'entries' => count($this->cache),
            'hits' => $hits,
            'misses' => $misses,
            'hitRate' => $hits > 0 ? round($hits / ($hits + $misses) * 100, 2) : 0,
            'ttl' => $this->cacheTtl
        ];
    }

    /**
     * Clear semua cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->logger->info('Reporting service cache cleared');
    }

    /**
     * Set cache TTL
     */
    public function setCacheTtl(int $seconds): void
    {
        $this->cacheTtl = max(60, $seconds); // Minimum 60 seconds
        $this->logger->info('Reporting service cache TTL updated', ['ttl' => $this->cacheTtl]);
    }
}

/**
 * Temporary stub classes untuk komponen yang akan diimplementasi nanti
 */
class ReportExporter
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function export(ReportResult $result, string $format): ExportJob
    {
        // Stub implementation - akan diimplementasi di Phase 3.2.2
        $this->logger->info('Exporting report', ['format' => $format]);
        return new ExportJob('export_' . uniqid(), $format, 'pending');
    }

    public function getStatus(string $jobId): ExportJob
    {
        // Stub implementation
        return new ExportJob($jobId, 'json', 'completed');
    }
}

class ReportScheduler
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function schedule(ReportSchedule $schedule): string
    {
        // Stub implementation - akan diimplementasi di Phase 3.2.2
        $scheduleId = 'schedule_' . uniqid();
        $this->logger->info('Report scheduled', ['scheduleId' => $scheduleId]);
        return $scheduleId;
    }

    public function cancel(string $scheduleId): bool
    {
        // Stub implementation
        $this->logger->info('Report schedule cancelled', ['scheduleId' => $scheduleId]);
        return true;
    }

    public function recordCacheHit(): void
    {
        // Untuk tracking cache performance
    }
}

class ReportAnalyzer
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function analyzeTrends(ReportResult $current, ReportResult $previous): array
    {
        // Stub implementation - akan diimplementasi di Phase 3.2.2
        $this->logger->info('Analyzing report trends');
        return [
            'trend' => 'stable',
            'changePercentage' => 0,
            'insights' => ['No significant trends detected']
        ];
    }
}