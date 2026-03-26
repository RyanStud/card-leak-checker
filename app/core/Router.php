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

        $normalizedMethod = strtoupper($method);

        if (!in_array($normalizedMethod, ['GET', 'POST'], true)) {
            $this->logSuspiciousMethodAbuse($path, $normalizedMethod, 'unsupported_method');
            http_response_code(405);
            echo 'Método não permitido';
            return;
        }

        $isRegisteredInGet = isset($this->getRoutes[$path]);
        $isRegisteredInPost = isset($this->postRoutes[$path]);

        if (($normalizedMethod === 'GET' && $isRegisteredInPost && !$isRegisteredInGet)
            || ($normalizedMethod === 'POST' && $isRegisteredInGet && !$isRegisteredInPost)
        ) {
            $this->logSuspiciousMethodAbuse($path, $normalizedMethod, 'method_route_mismatch');
            http_response_code(405);
            echo 'Método não permitido';
            return;
        }

        $routes = $normalizedMethod === 'POST' ? $this->postRoutes : $this->getRoutes;

        if (!isset($routes[$path])) {
            http_response_code(404);
            echo 'Página não encontrada';
            return;
        }

        [$controllerName, $action] = $routes[$path];
        $controller = new $controllerName();
        $controller->$action();
    }

    private function logSuspiciousMethodAbuse(string $path, string $method, string $reason): void
    {
        $suspicious = new SuspiciousEvent();
        $suspicious->create(
            !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
            $_SESSION['pre_2fa_email'] ?? null,
            client_ip(),
            'suspicious_method_abuse',
            json_encode([
                'uri' => $path,
                'method' => $method,
                'reason' => $reason,
            ], JSON_UNESCAPED_UNICODE)
        );
    }
}