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

        if ($monitor->isIpBlocked($ip)) {
            http_response_code(403);
            exit('Seu IP foi bloqueado temporariamente por segurança.');
        }

        $monitor->logRequest($ip, $uri, $method, $userAgent, $country, null);

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
}