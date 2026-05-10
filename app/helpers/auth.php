<?php

function session_guard_cookie_name(): string
{
    $cookieName = trim((string)env('SESSION_GUARD_COOKIE', 'cardleak_guard'));
    return $cookieName !== '' ? $cookieName : 'cardleak_guard';
}

function set_session_guard_cookie(string $value, int $expiresAt = 0): void
{
    $params = session_get_cookie_params();

    setcookie(session_guard_cookie_name(), $value, [
        'expires' => $expiresAt,
        'path' => (string)($params['path'] ?? '/'),
        'domain' => (string)($params['domain'] ?? ''),
        'secure' => (bool)($params['secure'] ?? false),
        'httponly' => true,
        'samesite' => (string)($params['samesite'] ?? 'Strict'),
    ]);
}

function bind_authenticated_session_context(): void
{
    $_SESSION['last_activity_at'] = time();
    $_SESSION['session_ip'] = client_ip();
    $_SESSION['session_ua_hash'] = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? '-'));

    $guard = bin2hex(random_bytes(32));
    $_SESSION['session_guard_hash'] = hash('sha256', $guard);
    set_session_guard_cookie($guard);
}

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

        set_session_guard_cookie('', time() - 42000);

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
