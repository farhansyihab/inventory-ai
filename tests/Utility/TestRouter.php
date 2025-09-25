<?php
// File: tests/Utility/TestRouter.php
declare(strict_types=1);

namespace Tests\Utility;

use App\Utility\Router;

class TestRouter extends Router
{
    public function dispatchTest(string $method, string $path, array $requestData = []): array
    {
        // Simulate request data
        if ($method === 'POST') {
            $_POST = $requestData;
        } else {
            $_GET = $requestData;
        }

        return $this->dispatch($method, $path);
    }

    protected function executeHandler($handler, array $params = []): mixed
    {
        if (is_array($handler) && count($handler) === 2) {
            [$controllerClass, $method] = $handler;
            
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                if (method_exists($controller, 'enableTestMode')) {
                    $controller->enableTestMode();
                }
                
                return call_user_func_array([$controller, $method], $params);
            }
        }
        
        return parent::executeHandler($handler, $params);
    }
}