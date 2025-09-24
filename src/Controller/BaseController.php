<?php
declare(strict_types=1);

namespace App\Controller;

use App\Utility\Logger;

/**
 * Base Controller with common functionality for all controllers
 */
abstract class BaseController
{
    protected Logger $logger;
    protected array $requestData = [];

    /** @var bool apakah berjalan dalam PHPUnit test mode */
    protected bool $testMode = false;

    /** @var array|null menyimpan response terakhir saat test */
    public ?array $lastResponse = null;

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger ?? new Logger();
        $this->parseRequestData();
    }

    /**
     * Aktifkan test mode (digunakan di PHPUnit)
     */
    public function enableTestMode(): void
    {
        $this->testMode = true;
    }

    /**
     * Parse request data dari JSON body, form POST, dan query GET
     */
    protected function parseRequestData(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (strpos($contentType, 'application/json') !== false) {
            $jsonInput = file_get_contents('php://input');
            $data = json_decode($jsonInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->requestData = $data ?? [];
            }
        } else {
            $this->requestData = $_POST ?? [];
        }

        $this->requestData = array_merge($this->requestData, $_GET ?? []);
    }

    /**
     * Ambil value dari request
     */
    protected function getRequestValue(string $key, $default = null)
    {
        return $this->requestData[$key] ?? $default;
    }

    /**
     * Ambil semua request data
     */
    protected function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * Kirim JSON response
     */
    protected function jsonResponse(array $data, int $statusCode = 200): ?array
    {
        if (!$this->testMode) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
            echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $this->lastResponse = [
                'status_code' => $statusCode,
                'body' => $data
            ];
            return $data;
        }
    }

    /**
     * Success response
     */
    protected function successResponse(array $data = [], string $message = 'Success', int $statusCode = 200): ?array
    {
        $response = [
            'status' => 'success',
            'message' => $message,
            'timestamp' => time(),
            'data' => $data
        ];
        return $this->jsonResponse($response, $statusCode);
    }

    /**
     * Error response
     */
    protected function errorResponse(string $message, array $errors = [], int $statusCode = 400): ?array
    {
        $response = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => time(),
            'errors' => $errors
        ];
        return $this->jsonResponse($response, $statusCode);
    }

    /**
     * Not found response
     */
    protected function notFoundResponse(string $message = 'Resource not found'): ?array
    {
        return $this->errorResponse($message, [], 404);
    }

    /**
     * Unauthorized response
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): ?array
    {
        return $this->errorResponse($message, [], 401);
    }

    /**
     * Validation error response
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): ?array
    {
        return $this->errorResponse($message, $errors, 422);
    }

    /**
     * Get authenticated user ID (dummy, implementasi JWT nanti)
     */
    protected function getAuthUserId(): ?string
    {
        return null;
    }

    /**
     * Cek apakah user terautentikasi
     */
    protected function isAuthenticated(): bool
    {
        return $this->getAuthUserId() !== null;
    }

    /**
     * Validasi field wajib
     */
    protected function validateRequiredFields(array $fields): array
    {
        $errors = [];
        foreach ($fields as $field) {
            if (!isset($this->requestData[$field]) || empty($this->requestData[$field])) {
                $errors[$field] = "The {$field} field is required";
            }
        }
        return $errors;
    }

    /**
     * Logging aksi controller
     */
    protected function logAction(string $action, array $context = []): void
    {
        $this->logger->info("Controller Action: {$action}", array_merge([
            'controller' => static::class,
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ], $context));
    }

    /**
     * Ambil parameter pagination
     */
    protected function getPaginationParams(): array
    {
        $page = max(1, (int)$this->getRequestValue('page', 1));
        $limit = max(1, min(100, (int)$this->getRequestValue('limit', 20)));
        $offset = ($page - 1) * $limit;

        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Ambil parameter sorting
     */
    protected function getSortingParams(): array
    {
        $sortBy = $this->getRequestValue('sort_by', 'createdAt');
        $sortOrder = strtolower($this->getRequestValue('sort_order', 'desc'));
        if (!in_array($sortOrder, ['asc', 'desc'])) {
            $sortOrder = 'desc';
        }
        return [
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ];
    }
}
