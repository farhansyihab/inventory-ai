<?php
// src/Exception/DashboardException.php

namespace App\Exception;

use RuntimeException;
use Throwable;

class DashboardException extends RuntimeException
{
    public const SERVICE_UNAVAILABLE = 'DASH_001';
    public const INVALID_DATA = 'DASH_002';
    public const CACHE_ERROR = 'DASH_003';

    private string $errorCode;
    private array $context;

    public function __construct(
        string $message = '',
        string $errorCode = '',
        array $context = [],
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->context = $context;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public static function serviceUnavailable(string $serviceName, Throwable $previous = null): self
    {
        return new self(
            sprintf('Service %s is temporarily unavailable', $serviceName),
            self::SERVICE_UNAVAILABLE,
            ['service' => $serviceName],
            503,
            $previous
        );
    }

    public static function invalidData(string $message, array $context = []): self
    {
        return new self(
            $message,
            self::INVALID_DATA,
            $context,
            400
        );
    }

    public static function cacheError(string $message, Throwable $previous = null): self
    {
        return new self(
            $message,
            self::CACHE_ERROR,
            [],
            500,
            $previous
        );
    }
}