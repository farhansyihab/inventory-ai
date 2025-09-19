<?php
declare(strict_types=1);

namespace App\Middleware;

use Psr\Log\LoggerInterface;
use App\Utility\Logger as AppLogger;

/**
 * Global Error Handler Middleware
 */
class ErrorHandler
{
    private LoggerInterface $logger;
    private bool $displayErrors;

    public function __construct(?LoggerInterface $logger = null, bool $displayErrors = false)
    {
        $this->logger = $logger ?? new AppLogger();
        $this->displayErrors = $displayErrors;
    }

    /**
     * Register error handlers
     */
    public function register(): void
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle PHP errors
     */
    public function handleError(int $errno, string $errstr, string $errfile, int $errline): bool
    {
        $errorType = $this->getErrorType($errno);
        
        $this->logger->error("PHP {$errorType}: {$errstr} in {$errfile}:{$errline}", [
            'errno' => $errno,
            'errfile' => $errfile,
            'errline' => $errline
        ]);

        // Don't execute PHP internal error handler
        return true;
    }

    /**
     * Handle uncaught exceptions
     */
    public function handleException(\Throwable $exception): void
    {
        $this->logger->error("Uncaught Exception: " . $exception->getMessage(), [
            'exception' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ]);

        $this->sendErrorResponse($exception);
    }

    /**
     * Handle shutdown errors (fatal errors)
     */
    public function handleShutdown(): void
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->logger->error("Shutdown Error: {$error['message']} in {$error['file']}:{$error['line']}", [
                'type' => $error['type'],
                'file' => $error['file'],
                'line' => $error['line']
            ]);

            $exception = new \ErrorException(
                $error['message'], 0, $error['type'], $error['file'], $error['line']
            );
            
            $this->sendErrorResponse($exception);
        }
    }

    /**
     * Send appropriate error response
     */
    private function sendErrorResponse(\Throwable $exception): void
    {
        if (headers_sent()) {
            return;
        }

        http_response_code(500);
        header('Content-Type: application/json');

        $response = [
            'status' => 'error',
            'message' => 'Internal Server Error',
            'timestamp' => time()
        ];

        if ($this->displayErrors) {
            $response['error'] = [
                'message' => $exception->getMessage(),
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine()
            ];
        }

        echo json_encode($response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Convert error number to error type name
     */
    private function getErrorType(int $errno): string
    {
        $errorTypes = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        ];

        return $errorTypes[$errno] ?? "E_UNKNOWN ($errno)";
    }

    /**
     * Set whether to display errors in response
     */
    public function setDisplayErrors(bool $displayErrors): void
    {
        $this->displayErrors = $displayErrors;
    }
}