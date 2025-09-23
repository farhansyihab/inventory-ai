<?php
declare(strict_types=1);

namespace App\Service;

/**
 * Interface untuk AI Strategy pattern
 */
interface AIStrategy
{
    /**
     * Analyze data dengan AI
     */
    public function analyze(array $data, string $analysisType = 'stock_prediction'): array;

    /**
     * Generate report/laporan dengan AI
     */
    public function generate(array $data, string $reportType = 'summary'): array;

    /**
     * Check jika strategy available
     */
    public function isAvailable(): bool;
}