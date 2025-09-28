<?php
declare(strict_types=1);

namespace App\Service\Reporting;

use App\Model\Report\ReportDefinition;
use App\Model\Report\ReportResult;

/**
 * Interface untuk Report Builders
 */
interface IReportBuilder
{
    public function buildReport(ReportDefinition $definition): ReportResult;
    public function buildComparativeReport(ReportDefinition $definition, array $comparisonData): ReportResult;
    public function buildPredictiveReport(ReportDefinition $definition, int $forecastDays): ReportResult;
    public function buildRealTimeReport(ReportDefinition $definition): ReportResult;
}
