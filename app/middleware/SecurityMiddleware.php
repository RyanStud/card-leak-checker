<?php

class SecurityMiddleware
{
    public static function handle(): void
    {
        $ip = client_ip();
        $country = client_country();
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '-';

        $monitor = new SecurityMonitor();
        $actionType = self::resolveActionType($method, $uri);
        $requestLogId = $monitor->logRequest(
            $ip,
            $uri,
            $method,
            $userAgent,
            $country,
            isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
            null,
            $actionType
        );

        register_shutdown_function(static function () use ($monitor, $requestLogId): void {
            if ($requestLogId === null) {
                return;
            }

            $responseCode = http_response_code();
            if (!is_int($responseCode) || $responseCode < 100) {
                $responseCode = 200;
            }

            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $monitor->finalizeRequestLog($requestLogId, $userId, $responseCode);
        });

        if ($monitor->isIpBlocked($ip)) {
            http_response_code(403);
            exit('Seu IP foi bloqueado temporariamente por segurança.');
        }

        $retentionDays = (int)env('SECURITY_LOG_RETENTION_DAYS', 90);
        $cleanupProbability = (int)env('SECURITY_LOG_CLEANUP_PROBABILITY', 2);
        if ($cleanupProbability > 0 && random_int(1, 100) <= min(100, $cleanupProbability)) {
            try {
                $cleanupResult = $monitor->cleanupOldSecurityData($retentionDays);
                app_log('security_cleanup retention_days=' . max(1, $retentionDays) . ' deleted=' . json_encode($cleanupResult));
            } catch (Throwable $e) {
                app_log('security_cleanup_error ' . $e->getMessage());
            }
        }

        $recentRequests = $monitor->countRecentRequestsByIp($ip, 1);
        if ($recentRequests > 120) {
            $monitor->blockIp(
                $ip,
                'global_rate_limit_exceeded',
                date('Y-m-d H:i:s', time() + 3600)
            );

            $suspicious = new SuspiciousEvent();
            $suspicious->create(
                null,
                null,
                $ip,
                'global_rate_limit_exceeded',
                json_encode([
                    'requests_last_minute' => $recentRequests,
                    'uri' => $uri,
                ], JSON_UNESCAPED_UNICODE)
            );

            http_response_code(429);
            exit('Muitas requisições. Tente novamente mais tarde.');
        }

        if (is_suspicious_path($uri)) {
            $suspicious = new SuspiciousEvent();
            $suspicious->create(
                null,
                null,
                $ip,
                'scanner_path_detected',
                json_encode([
                    'uri' => $uri,
                    'method' => $method,
                    'country' => $country,
                ], JSON_UNESCAPED_UNICODE)
            );

            $recentSuspicious = $monitor->countRecentSuspiciousByIp($ip, 15);
            if ($recentSuspicious >= 5) {
                $monitor->blockIp(
                    $ip,
                    'scanner_detected',
                    date('Y-m-d H:i:s', time() + 86400)
                );
            }
        }
    }

    private static function resolveActionType(string $method, string $uri): string
    {
        $normalizedMethod = strtoupper($method);

        if ($normalizedMethod === 'GET') {
            return 'read';
        }

        if ($normalizedMethod !== 'POST') {
            return 'other';
        }

        $path = strtolower((string)(parse_url($uri, PHP_URL_PATH) ?? '/'));
        $changeKeywords = [
            'update',
            'delete',
            'remove',
            'revoke',
            'reject',
            'approve',
            'import',
            'unlink',
            'password',
            'profile',
            'role',
            'save',
            'setup',
            'verify',
            'logout',
            'reset',
            'elevate',
        ];

        foreach ($changeKeywords as $keyword) {
            if (str_contains($path, $keyword)) {
                return 'change';
            }
        }

        return 'write';
    }
}