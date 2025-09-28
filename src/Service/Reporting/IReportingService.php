<?php
declare(strict_types=1);

namespace App\Service\Reporting;

use App\Model\Report\ReportDefinition;
use App\Model\Report\ReportResult;
use App\Model\Report\ReportSchedule;
use App\Model\Report\ExportJob;

/**
 * Interface untuk Reporting Service
 * Mengikuti pola yang konsisten dengan interface lainnya di codebase
 */
interface IReportingService
{
    /**
     * Generate report berdasarkan definition
     */
    public function generateReport(ReportDefinition $definition): ReportResult;

    /**
     * Generate real-time report (streaming data)
     */
    public function generateRealTimeReport(string $type, array $filters = []): ReportResult;

    /**
     * Schedule report untuk eksekusi berkala
     */
    public function scheduleReport(ReportSchedule $schedule): string;

    /**
     * Cancel scheduled report
     */
    public function cancelSchedule(string $scheduleId): bool;

    /**
     * Get report history berdasarkan type dan range
     */
    public function getReportHistory(string $reportType, DateRange $range): array;

    /**
     * Export report result ke format tertentu
     */
    public function exportReport(ReportResult $result, string $format): ExportJob;

    /**
     * Get export job status
     */
    public function getExportStatus(string $jobId): ExportJob;

    /**
     * Analyze trends antara current dan previous report
     */
    public function analyzeReportTrends(ReportResult $current, ReportResult $previous): array;

    /**
     * Generate predictive report dengan forecast period
     */
    public function generatePredictiveReport(string $type, int $forecastPeriod): ReportResult;

    /**
     * Get available report types
     */
    public function getAvailableReportTypes(): array;

    /**
     * Validate report definition sebelum generate
     */
    public function validateReportDefinition(ReportDefinition $definition): array;

    /**
     * Test report generation dengan sample data
     */
    public function testReportGeneration(ReportDefinition $definition): ReportResult;
}

/**
 * Supporting class untuk date range
 */
class DateRange
{
    private \DateTime $start;
    private \DateTime $end;

    public function __construct(\DateTime $start, \DateTime $end)
    {
        if ($start > $end) {
            throw new \InvalidArgumentException('Start date cannot be after end date');
        }

        $this->start = $start;
        $this->end = $end;
    }

    public function getStart(): \DateTime
    {
        return $this->start;
    }

    public function getEnd(): \DateTime
    {
        return $this->end;
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start->format(\DateTimeInterface::ATOM),
            'end' => $this->end->format(\DateTimeInterface::ATOM)
        ];
    }

    public static function createLast7Days(): self
    {
        $end = new \DateTime();
        $start = clone $end;
        $start->modify('-7 days');

        return new self($start, $end);
    }

    public static function createThisMonth(): self
    {
        $start = new \DateTime('first day of this month');
        $end = new \DateTime('last day of this month');

        return new self($start, $end);
    }

    public static function createLast30Days(): self
    {
        $end = new \DateTime();
        $start = clone $end;
        $start->modify('-30 days');

        return new self($start, $end);
    }
}b