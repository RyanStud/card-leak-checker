<?php

class AuthMiddleware
{
    public static function handle(): void
    {
        $idleTimeout = (int) env('SESSION_IDLE_TIMEOUT', 3600);
        if ($idleTimeout < 60) {
            $idleTimeout = 3600;
        }

        if (empty($_SESSION['user_id'])) {
            header('Location: ' . base_path('/login'));
            exit;
        }

        $lastActivity = (int) ($_SESSION['last_activity_at'] ?? 0);
        if ($lastActivity > 0 && (time() - $lastActivity) > $idleTimeout) {
            logout_user();
            set_flash('error', 'Sua sessão expirou por inatividade. Faça login novamente.');
            header('Location: ' . base_path('/login'));
            exit;
        }

        $_SESSION['last_activity_at'] = time();

        if (empty($_SESSION['two_factor_verified'])) {
            header('Location: ' . base_path('/2fa/verify'));
            exit;
        }
    }
}
