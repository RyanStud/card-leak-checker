<?php

function base_path(string $path = ''): string
{
    $base = rtrim(parse_url((string) env('APP_URL', ''), PHP_URL_PATH) ?? '', '/');

    if ($path === '') {
        return $base !== '' ? $base : '/';
    }

    return ($base !== '' ? $base : '') . '/' . ltrim($path, '/');
}
