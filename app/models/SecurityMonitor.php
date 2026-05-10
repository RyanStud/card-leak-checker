<?php

class SecurityMonitor
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function logRequest(
        string $ip,
        string $uri,
        string $method,
        ?string $userAgent,
        ?string $country,
        ?int $userId = null,
        ?int $responseCode = null,
        string $actionType = 'other'
    ): ?int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO request_logs (user_id, ip_address, request_uri, request_method, action_type, user_agent, country, response_code)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $saved = $stmt->execute([$userId, $ip, $uri, $method, $actionType, $userAgent, $country, $responseCode]);
        if (!$saved) {
            return null;
        }

        return (int)$this->pdo->lastInsertId();
    }

    public function finalizeRequestLog(int $requestLogId, ?int $userId, int $responseCode): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE request_logs
             SET user_id = COALESCE(user_id, ?), response_code = ?
             WHERE id = ?'
        );

        return $stmt->execute([$userId, $responseCode, $requestLogId]);
    }

    public function isIpBlocked(string $ip): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM blocked_ips
             WHERE ip_address = ?
               AND (blocked_until IS NULL OR blocked_until >= NOW())
             LIMIT 1'
        );
        $stmt->execute([$ip]);

        return (bool)$stmt->fetch();
    }

    public function blockIp(string $ip, string $reason, ?string $blockedUntil = null): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO blocked_ips (ip_address, reason, blocked_until)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE reason = VALUES(reason), blocked_until = VALUES(blocked_until)'
        );

        return $stmt->execute([$ip, $reason, $blockedUntil]);
    }

    public function countRecentRequestsByIp(string $ip, int $minutes = 1): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM request_logs
             WHERE ip_address = ?
               AND created_at >= (NOW() - INTERVAL ? MINUTE)'
        );
        $stmt->execute([$ip, $minutes]);
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }

    public function countRecentSuspiciousByIp(string $ip, int $minutes = 15): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM suspicious_events
             WHERE ip_address = ?
               AND created_at >= (NOW() - INTERVAL ? MINUTE)'
        );
        $stmt->execute([$ip, $minutes]);
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }

    public function getBlockedIps(): array
    {
        $stmt = $this->pdo->query(
            'SELECT *
             FROM blocked_ips
             ORDER BY created_at DESC'
        );

        return $stmt->fetchAll();
    }

    public function getRecentRequests(int $limit = 50): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM request_logs
             ORDER BY created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getTopCountries(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT country, COUNT(*) AS total
             FROM request_logs
             GROUP BY country
             ORDER BY total DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function cleanupOldSecurityData(int $retentionDays): array
    {
        $retentionDays = max(1, $retentionDays);

        $queries = [
            'request_logs' => 'DELETE FROM request_logs WHERE created_at < (NOW() - INTERVAL ? DAY)',
            'suspicious_events' => 'DELETE FROM suspicious_events WHERE created_at < (NOW() - INTERVAL ? DAY)',
            'login_attempts' => 'DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL ? DAY)',
            'blocked_ips' => 'DELETE FROM blocked_ips WHERE blocked_until IS NOT NULL AND blocked_until < (NOW() - INTERVAL ? DAY)',
        ];

        $deletedByTable = [];

        foreach ($queries as $table => $sql) {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$retentionDays]);
            $deletedByTable[$table] = $stmt->rowCount();
        }

        return $deletedByTable;
    }
}