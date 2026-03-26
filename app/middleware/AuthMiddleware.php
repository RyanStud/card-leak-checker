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

        $currentIp = client_ip();
        $currentUaHash = hash('sha256', (string)($_SERVER['HTTP_USER_AGENT'] ?? '-'));

        $storedIp = (string)($_SESSION['session_ip'] ?? '');
        $storedUaHash = (string)($_SESSION['session_ua_hash'] ?? '');

        if ($storedIp === '' || $storedUaHash === '') {
            $_SESSION['session_ip'] = $currentIp;
            $_SESSION['session_ua_hash'] = $currentUaHash;
            return;
        }

        $ipChanged = $storedIp !== $currentIp;
        $uaChanged = $storedUaHash !== $currentUaHash;

        if ($ipChanged && $uaChanged) {
            $suspicious = new SuspiciousEvent();
            $suspicious->create(
                (int)$_SESSION['user_id'],
                null,
                $currentIp,
                'session_hijack_suspected',
                json_encode([
                    'previous_ip' => $storedIp,
                    'previous_ua_hash' => $storedUaHash,
                    'current_ip' => $currentIp,
                    'current_ua_hash' => $currentUaHash,
                ], JSON_UNESCAPED_UNICODE)
            );

            logout_user();
            set_flash('error', 'Sua sessão foi encerrada por mudança suspeita de contexto. Faça login novamente.');
            header('Location: ' . base_path('/login'));
            exit;
        }

        if ($ipChanged) {
            $_SESSION['session_ip'] = $currentIp;
        }

        if ($uaChanged) {
            $_SESSION['session_ua_hash'] = $currentUaHash;
        }
    }
}
