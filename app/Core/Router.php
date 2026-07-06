<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes['GET'][$this->normalisePath($path)] = $handler;
    }

public function post(string $path, array $handler): void
{
    $this->routes['POST'][$this->normalisePath($path)] = $handler;
}

public function dispatch(): void
{
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
        $basePath = rtrim(str_replace('/index.php', '', $scriptName), '/');

        $path = $uri;

        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

    $path = $this->normalisePath($path);

    if ($path === '/index.php') {
        $path = '/';
    }

if (!isset($this->routes[$method][$path])) {
    http_response_code(404);
    echo '<h1>404 - Page not found</h1>';
    echo '<p>Detected path: ' . htmlspecialchars($path) . '</p>';
    return;
}

[$controller, $action] = $this->routes[$method][$path];

$controllerInstance = new $controller();
$controllerInstance->$action();
}

private function normalisePath(string $path): string
{
    $path = '/' . trim($path, '/');

    return $path === '//' ? '/' : $path;
}
}
