<?php
// File: src/Service/IInventoryAnalysisService.php
declare(strict_types=1);

namespace App\Service;

interface IInventoryAnalysisService
{
    /**
     * Get comprehensive inventory analysis dengan AI enhancement
     */
    public function getComprehensiveAnalysis(array $options = []): array;

    /**
     * Generate weekly inventory report dengan AI insights
     */
    public function generateWeeklyReport(): array;

    /**
     * Monitor critical items dan generate alerts
     */
    public function monitorCriticalItems(): array;

    /**
     * Predictive analysis untuk inventory needs
     */
    public function predictInventoryNeeds(int $forecastDays = 30): array;

    /**
     * Optimize entire inventory berdasarkan AI analysis
     */
    public function optimizeInventory(): array;
}