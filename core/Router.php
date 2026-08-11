<?php

class Router
{
    private array $routes = [
        'GET' => [],
        'POST' => [],
    ];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function resolve(string $requestUri): void
    {
        $path = request_path();
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $handler = $this->routes[$method][$path] ?? null;

        if (!$handler) {
            http_response_code(404);
            echo "404 Not Found";
            return;
        }

        if ($method === 'POST') {
            verify_csrf();
        }

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logAuthenticatedVisit($path, $method);
        }

        call_user_func($handler);
    }
}
