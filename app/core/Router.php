<?php

class Router
{
    private array $getRoutes = [];
    private array $postRoutes = [];

    public function get(string $path, array $handler): void
    {
        $this->getRoutes[$path] = $handler;
    }

    public function post(string $path, array $handler): void
    {
        $this->postRoutes[$path] = $handler;
    }

    public function dispatch(string $uri, string $method): void
    {
        $path = parse_url($uri, PHP_URL_PATH);

        $basePath = rtrim(parse_url((string) env('APP_URL', ''), PHP_URL_PATH) ?? '', '/');

        if ($path !== null && $basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath));
        }

        if ($path === '' || $path === null) {
            $path = '/';
        }

        $routes = strtoupper($method) === 'POST' ? $this->postRoutes : $this->getRoutes;

        if (!isset($routes[$path])) {
            http_response_code(404);
            echo 'Página não encontrada';
            return;
        }

        [$controllerName, $action] = $routes[$path];
        $controller = new $controllerName();
        $controller->$action();
    }
}