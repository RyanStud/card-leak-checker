<?php

class AdminMiddleware
{
    public static function requireAdminRole(): array
    {
        AuthMiddleware::handle();

        $userModel = new User();
        $user = $userModel->findById((int)$_SESSION['user_id']);

        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            $ip = client_ip();
            $uri = $_SERVER['REQUEST_URI'] ?? '/';
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

            $suspicious = new SuspiciousEvent();
            $suspicious->create(
                (int)($_SESSION['user_id'] ?? 0) ?: null,
                $user['email'] ?? null,
                $ip,
                'privilege_escalation_attempt',
                json_encode([
                    'target' => 'admin_area',
                    'uri' => $uri,
                    'method' => $method,
                    'role' => $user['role'] ?? 'unknown',
                ], JSON_UNESCAPED_UNICODE)
            );

            $recentDenied = $suspicious->countRecentByIpAndType($ip, 'privilege_escalation_attempt', 15);
            if ($recentDenied >= 3) {
                $suspicious->create(
                    (int)($_SESSION['user_id'] ?? 0) ?: null,
                    $user['email'] ?? null,
                    $ip,
                    'admin_access_denied_repeated',
                    json_encode([
                        'window_minutes' => 15,
                        'attempts' => $recentDenied,
                        'uri' => $uri,
                    ], JSON_UNESCAPED_UNICODE)
                );
            }

            http_response_code(403);
            exit('Acesso restrito ao administrador.');
        }

        if (!can_view_admin_area()) {
            set_flash('error', 'Sessao sem permissao para funcoes administrativas.');
            header('Location: ' . base_path('/dashboard'));
            exit;
        }

        return $user;
    }

    public static function handle(): void
    {
        $user = self::requireAdminRole();

        $elevatedUntil = (int)($_SESSION['admin_elevated_until'] ?? 0);
        if ($elevatedUntil < time()) {
            $_SESSION['admin_elevated_until'] = 0;

            $suspicious = new SuspiciousEvent();
            $suspicious->create(
                (int)($_SESSION['user_id'] ?? 0) ?: null,
                $user['email'] ?? null,
                client_ip(),
                'admin_session_not_elevated',
                json_encode([
                    'uri' => $_SERVER['REQUEST_URI'] ?? '/',
                    'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
                ], JSON_UNESCAPED_UNICODE)
            );

            set_flash('error', 'Para acessar funcoes de administrador, confirme a elevacao da sessao via Telegram.');
            header('Location: ' . base_path('/admin/elevate'));
            exit;
        }
    }
}