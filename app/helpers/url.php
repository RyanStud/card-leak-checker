<?php

function base_path(string $path = ''): string
{
    $base = rtrim(parse_url((string) env('APP_URL', ''), PHP_URL_PATH) ?? '', '/');

    if ($path === '') {
        return $base !== '' ? $base : '/';
    }

    return ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
}

function current_path(): string
{
    $requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
    $basePath = rtrim(parse_url((string) env('APP_URL', ''), PHP_URL_PATH) ?? '', '/');

    if ($basePath !== '' && str_starts_with($requestPath, $basePath)) {
        $requestPath = substr($requestPath, strlen($basePath));
    }

    return $requestPath === '' ? '/' : $requestPath;
}
