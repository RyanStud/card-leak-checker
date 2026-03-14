<?php

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $seed = bin2hex(random_bytes(32));
        $secret = (string) env('CSRF_SECRET', 'csrf_local_secret');
        $_SESSION['csrf_token'] = hash_hmac('sha256', $seed, $secret);
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['_csrf'] ?? '';

    if (
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $token)
    ) {
        http_response_code(419);
        exit('CSRF inválido');
    }
}