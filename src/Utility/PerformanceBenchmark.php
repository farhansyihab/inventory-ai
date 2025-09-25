<?php
// File: src/Utility/PerformanceBenchmark.php (Fixed)
declare(strict_types=1);

namespace App\Utility;

class PerformanceBenchmark
{
    private static array $benchmarks = [];
    private static bool $enabled = true;

    public static function enable(): void
    {
        self::$enabled = true;
    }

    public static function disable(): void
    {
        self::$enabled = false;
    }

    public static function measure(callable $fn, string $operation, array $context = []): mixed
    {
        if (!self::$enabled) {
            return $fn();
        }

        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);
        
        try {
            $result = $fn();
            
            $endTime = microtime(true);
            $memoryAfter = memory_get_usage(true);
            
            $duration = $endTime - $startTime;
            $memoryUsed = $memoryAfter - $memoryBefore;
            
            self::recordBenchmark($operation, $duration, $memoryUsed, $context);
            
            return $result;
            
        } catch (\Exception $e) {
            $endTime = microtime(true);
            $duration = $endTime - $startTime;
            
            self::recordBenchmark($operation, $duration, 0, array_merge($context, [
                'error' => $e->getMessage(),
                'status' => 'failed'
            ]));
            
            throw $e;
        }
    }

    public static function measureBatch(array $operations, bool $parallel = false): array
    {
        $results = [];
        
        if ($parallel && extension_loaded('parallel')) {
            $results = self::measureParallel($operations);
        } else {
            // PERBAIKAN: Pastikan key adalah string
            foreach ($operations as $name => $operation) {
                if (is_int($name)) {
                    $name = "operation_{$name}";
                }
                $results[$name] = self::measure($operation, $name);
            }
        }
        
        return $results;
    }

    public static function getResults(): array
    {
        return self::$benchmarks;
    }

    public static function getLatestResult(): ?array
    {
        // PERBAIKAN: Handle empty array
        if (empty(self::$benchmarks)) {
            return null;
        }
        return end(self::$benchmarks);
    }

    public static function clear(): void
    {
        self::$benchmarks = [];
    }

    public static function generateReport(): array
    {
        if (empty(self::$benchmarks)) {
            return ['message' => 'No benchmarks recorded'];
        }

        $totalDuration = 0;
        $totalMemory = 0;
        $slowestOperation = '';
        $maxDuration = 0;
        $operationCounts = [];

        foreach (self::$benchmarks as $benchmark) {
            $totalDuration += $benchmark['duration'];
            $totalMemory += $benchmark['memory_used'];
            
            if ($benchmark['duration'] > $maxDuration) {
                $maxDuration = $benchmark['duration'];
                $slowestOperation = $benchmark['operation'];
            }
            
            $operationType = $benchmark['operation'];
            $operationCounts[$operationType] = ($operationCounts[$operationType] ?? 0) + 1;
        }

        $averageDuration = $totalDuration / count(self::$benchmarks);
        $averageMemory = $totalMemory / count(self::$benchmarks);

        return [
            'summary' => [
                'total_operations' => count(self::$benchmarks),
                'total_duration_seconds' => round($totalDuration, 4),
                'total_memory_bytes' => $totalMemory,
                'total_memory_mb' => round($totalMemory / 1024 / 1024, 2),
                'average_duration_seconds' => round($averageDuration, 4),
                'average_memory_bytes' => round($averageMemory, 2),
                'slowest_operation' => [
                    'name' => $slowestOperation,
                    'duration_seconds' => round($maxDuration, 4)
                ]
            ],
            'operation_counts' => $operationCounts,
            'benchmarks' => self::$benchmarks
        ];
    }

    public static function meetsThreshold(string $operation, float $maxDuration, int $maxMemory): bool
    {
        $benchmarks = array_filter(self::$benchmarks, fn($b) => $b['operation'] === $operation);
        
        if (empty($benchmarks)) {
            return false;
        }

        $latest = end($benchmarks);
        
        return $latest['duration'] <= $maxDuration && $latest['memory_used'] <= $maxMemory;
    }

    private static function recordBenchmark(string $operation, float $duration, int $memoryUsed, array $context = []): void
    {
        $benchmark = [
            'operation' => $operation,
            'duration' => $duration,
            'duration_ms' => round($duration * 1000, 2),
            'memory_used' => $memoryUsed,
            'memory_used_mb' => round($memoryUsed / 1024 / 1024, 4),
            'timestamp' => microtime(true),
            'datetime' => date('Y-m-d H:i:s'),
            'context' => $context
        ];

        self::$benchmarks[] = $benchmark;
    }

    private static function measureParallel(array $operations): array
    {
        $results = [];
        $futures = [];

        // PERBAIKAN: Handle numeric keys
        foreach ($operations as $name => $operation) {
            if (is_int($name)) {
                $name = "operation_{$name}";
            }
            
            $runtime = new \parallel\Runtime();
            $futures[$name] = $runtime->run(function() use ($operation, $name) {
                $startTime = microtime(true);
                $memoryBefore = memory_get_usage(true);
                
                $result = $operation();
                
                return [
                    'result' => $result,
                    'duration' => microtime(true) - $startTime,
                    'memory_used' => memory_get_usage(true) - $memoryBefore
                ];
            });
        }

        foreach ($futures as $name => $future) {
            $result = $future->value();
            $results[$name] = $result['result'];
            
            self::recordBenchmark($name, $result['duration'], $result['memory_used']);
        }

        return $results;
    }
}