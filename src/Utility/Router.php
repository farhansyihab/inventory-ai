<?php
declare(strict_types=1);

namespace App\Utility;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Simple Router implementation for HTTP request routing
 */
class Router
{
    private array $routes = [];
    private array $routeGroups = [];
    private $notFoundHandler;
    private $currentGroupPrefix = '';
    private bool $testMode = false;

    // HTTP Methods
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_OPTIONS = 'OPTIONS';

    public function __construct()
    {
        // Initialize with common HTTP methods
        $this->routes = [
            self::METHOD_GET => [],
            self::METHOD_POST => [],
            self::METHOD_PUT => [],
            self::METHOD_DELETE => [],
            self::METHOD_PATCH => [],
            self::METHOD_OPTIONS => [],
        ];
    }

    /**
    * Aktifkan test mode (skip http_response_code)
    */
    public function enableTestMode(): void
    {
        $this->testMode = true;
    }    

    /**
     * Add a route for GET method
     */
    public function get(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_GET, $path, $handler);
    }

    /**
     * Add a route for POST method
     */
    public function post(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_POST, $path, $handler);
    }

    /**
     * Add a route for PUT method
     */
    public function put(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_PUT, $path, $handler);
    }

    /**
     * Add a route for DELETE method
     */
    public function delete(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_DELETE, $path, $handler);
    }

    /**
     * Add a route for PATCH method
     */
    public function patch(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_PATCH, $path, $handler);
    }

    /**
     * Add a route for OPTIONS method
     */
    public function options(string $path, $handler): self
    {
        return $this->addRoute(self::METHOD_OPTIONS, $path, $handler);
    }

    /**
     * Add a route for any HTTP method
     */
    public function any(string $path, $handler): self
    {
        foreach ($this->routes as $method => $_) {
            $this->addRoute($method, $path, $handler);
        }
        return $this;
    }

    /**
     * Add a route with custom HTTP method
     */
    public function addRoute(string $method, string $path, $handler): self
    {
        $method = strtoupper($method);
        $path = $this->currentGroupPrefix . $this->normalizePath($path);

        if (!isset($this->routes[$method])) {
            $this->routes[$method] = [];
        }

        $this->routes[$method][$path] = $handler;
        return $this;
    }

    /**
     * Group routes with a common prefix
     */
    public function group(string $prefix, callable $callback): self
    {
        $previousGroupPrefix = $this->currentGroupPrefix;
        $this->currentGroupPrefix .= $this->normalizePath($prefix);
        
        $callback($this);
        
        $this->currentGroupPrefix = $previousGroupPrefix;
        return $this;
    }

    /**
     * Set 404 Not Found handler
     */
    public function setNotFoundHandler(callable $handler): self
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Dispatch the request to appropriate handler
     */
    public function dispatch(string $method, string $path)
    {
        $method = strtoupper($method);
        $path = $this->normalizePath($path);

        // Check if method exists
        if (!isset($this->routes[$method])) {
            return $this->handleNotFound();
        }

        // Exact match
        if (isset($this->routes[$method][$path])) {
            return $this->executeHandler($this->routes[$method][$path]);
        }

        // Pattern matching with parameters
        foreach ($this->routes[$method] as $routePath => $handler) {
            if ($this->matchRoute($routePath, $path, $params)) {
                return $this->executeHandler($handler, $params);
            }
        }

        return $this->handleNotFound();
    }

    /**
     * Execute the route handler
     */
    private function executeHandler($handler, array $params = [])
    {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }

        if (is_string($handler) && strpos($handler, '@') !== false) {
            [$controller, $method] = explode('@', $handler, 2);
            $controllerInstance = new $controller();
            
            if (method_exists($controllerInstance, $method)) {
                return call_user_func_array([$controllerInstance, $method], $params);
            }
        }

        throw new \RuntimeException("Invalid route handler");
    }

    /**
     * Check if route path matches request path
     */
    private function matchRoute(string $routePath, string $requestPath, ?array &$params): bool
    {
        $params = [];
        
        // Convert route pattern to regex
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $routePath);
        $pattern = "#^$pattern$#";
        
        if (preg_match($pattern, $requestPath, $matches)) {
            // Extract named parameters
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }
            return true;
        }
        
        return false;
    }

    /**
     * Handle 404 Not Found
     */
    private function handleNotFound()
    {
        if ($this->notFoundHandler) {
            return $this->executeHandler($this->notFoundHandler);
        }

        // http_response_code(404);
        return [
            'status' => 'error',
            'message' => 'Not Found',
            'timestamp' => time()
        ];
    }

    /**
     * Normalize path by ensuring it starts with slash and doesn't end with slash
     */
    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? $path : rtrim($path, '/');
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Clear all routes
     */
    public function clearRoutes(): void
    {
        $this->routes = [
            self::METHOD_GET => [],
            self::METHOD_POST => [],
            self::METHOD_PUT => [],
            self::METHOD_DELETE => [],
            self::METHOD_PATCH => [],
            self::METHOD_OPTIONS => [],
        ];
        $this->currentGroupPrefix = '';
    }
}