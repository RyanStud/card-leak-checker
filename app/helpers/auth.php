<?php

function is_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . base_path('/login'));
        exit;
    }
}

function logout_user(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }
}

function is_admin_user(): bool
{
    if (!is_logged_in()) {
        return false;
    }

    $userModel = new User();
    $sessionUser = $userModel->findById((int)$_SESSION['user_id']);

    return (($sessionUser['role'] ?? 'user') === 'admin');
}

function can_view_admin_area(): bool
{
    if (!is_admin_user()) {
        return false;
    }

    return (($_SESSION['admin_access_mode'] ?? 'restricted') === 'privileged');
}
