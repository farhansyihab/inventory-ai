<?php
declare(strict_types=1);

namespace App\Controller;

use App\Utility\Logger;
use Psr\Http\Message\ResponseInterface;

/**
 * Base Controller with common functionality for all controllers
 */
abstract class BaseController
{
    protected Logger $logger;
    protected array $requestData = [];

    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger ?? new Logger();
        $this->parseRequestData();
    }

    /**
     * Parse request data from JSON input or form data
     */
    protected function parseRequestData(): void
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // Parse JSON data
        if (strpos($contentType, 'application/json') !== false) {
            $jsonInput = file_get_contents('php://input');
            $data = json_decode($jsonInput, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                $this->requestData = $data;
            }
        } 
        // Parse form data
        else {
            $this->requestData = $_POST;
        }

        // Merge with query parameters
        $this->requestData = array_merge($this->requestData, $_GET);
    }

    /**
     * Get request data by key with optional default value
     */
    protected function getRequestValue(string $key, $default = null)
    {
        return $this->requestData[$key] ?? $default;
    }

    /**
     * Send JSON response
     */
    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send success response
     */
    protected function successResponse(array $data = [], string $message = 'Success', int $statusCode = 200): void
    {
        $response = [
            'status' => 'success',
            'message' => $message,
            'timestamp' => time(),
            'data' => $data
        ];

        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Send error response
     */
    protected function errorResponse(string $message, array $errors = [], int $statusCode = 400): void
    {
        $response = [
            'status' => 'error',
            'message' => $message,
            'timestamp' => time(),
            'errors' => $errors
        ];

        $this->jsonResponse($response, $statusCode);
    }

    /**
     * Send not found response
     */
    protected function notFoundResponse(string $message = 'Resource not found'): void
    {
        $this->errorResponse($message, [], 404);
    }

    /**
     * Send unauthorized response
     */
    protected function unauthorizedResponse(string $message = 'Unauthorized'): void
    {
        $this->errorResponse($message, [], 401);
    }

    /**
     * Send validation error response
     */
    protected function validationErrorResponse(array $errors, string $message = 'Validation failed'): void
    {
        $this->errorResponse($message, $errors, 422);
    }

    /**
     * Get authenticated user ID (to be implemented with JWT)
     */
    protected function getAuthUserId(): ?string
    {
        // TODO: Implement JWT authentication
        return null;
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool
    {
        // TODO: Implement authentication check
        return $this->getAuthUserId() !== null;
    }

    /**
     * Validate required fields in request data
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
     * Log controller action
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
     * Get pagination parameters from request
     */
    protected function getPaginationParams(): array
    {
        $page = max(1, (int) $this->getRequestValue('page', 1));
        $limit = max(1, min(100, (int) $this->getRequestValue('limit', 20)));
        $offset = ($page - 1) * $limit;

        return [
            'page' => $page,
            'limit' => $limit,
            'offset' => $offset
        ];
    }

    /**
     * Get sorting parameters from request
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