<?php

class AdminDashboard
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getCounts(?string $since = null): array
    {
        return [
            'users' => $this->countTable('users', $since),
            'projects' => $this->countTable('projects', $since),
            'card_checks' => $this->countTable('card_check_requests', $since),
            'audit_logs' => $this->countTable('audit_logs', $since),
            'login_attempts' => $this->countTable('login_attempts', $since),
            'suspicious_events' => $this->countTable('suspicious_events', $since),
            'password_resets' => $this->countTable('password_resets', $since),
            'blocked_ips' => $this->countTable('blocked_ips', $since),
            'request_logs' => $this->countTable('request_logs', $since),
        ];
    }

    private function countTable(string $table, ?string $since = null): int
    {
        $allowed = [
            'users',
            'projects',
            'card_check_requests',
            'audit_logs',
            'login_attempts',
            'suspicious_events',
            'password_resets',
            'blocked_ips',
            'request_logs',
        ];

        if (!in_array($table, $allowed, true)) {
            throw new InvalidArgumentException('Tabela inválida.');
        }

        $timeColumns = [
            'users' => 'created_at',
            'projects' => 'created_at',
            'card_check_requests' => 'checked_at',
            'audit_logs' => 'created_at',
            'login_attempts' => 'attempted_at',
            'suspicious_events' => 'created_at',
            'password_resets' => 'created_at',
            'blocked_ips' => 'created_at',
            'request_logs' => 'created_at',
        ];

        if ($since !== null && isset($timeColumns[$table])) {
            $column = $timeColumns[$table];
            $stmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM {$table} WHERE {$column} >= ?");
            $stmt->execute([$since]);
        } else {
            $stmt = $this->pdo->query("SELECT COUNT(*) AS total FROM {$table}");
        }

        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }

    public function getRecentAuditLogs(int $limit = 20, ?string $since = null): array
    {
        if ($since !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT al.*, u.email
                 FROM audit_logs al
                 LEFT JOIN users u ON u.id = al.user_id
                 WHERE al.created_at >= ?
                 ORDER BY al.created_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $since, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT al.*, u.email
                 FROM audit_logs al
                 LEFT JOIN users u ON u.id = al.user_id
                 ORDER BY al.created_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getRecentSuspiciousEvents(int $limit = 20, ?string $since = null): array
    {
        if ($since !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT se.*, COALESCE(se.email, u.email) AS email
                 FROM suspicious_events se
                 LEFT JOIN users u ON u.id = se.user_id
                 WHERE se.created_at >= ?
                 ORDER BY se.created_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $since, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT se.*, COALESCE(se.email, u.email) AS email
                 FROM suspicious_events se
                 LEFT JOIN users u ON u.id = se.user_id
                 ORDER BY se.created_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getRecentLoginAttempts(int $limit = 30, ?string $since = null): array
    {
        if ($since !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT *
                 FROM login_attempts
                 WHERE attempted_at >= ?
                 ORDER BY attempted_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $since, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT *
                 FROM login_attempts
                 ORDER BY attempted_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getTopIps(int $limit = 10, ?string $since = null): array
    {
        if ($since !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT ip_address, COUNT(*) AS total
                 FROM login_attempts
                 WHERE attempted_at >= ?
                 GROUP BY ip_address
                 ORDER BY total DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $since, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT ip_address, COUNT(*) AS total
                 FROM login_attempts
                 GROUP BY ip_address
                 ORDER BY total DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getSuspiciousEventTypes(?string $since = null): array
    {
        if ($since !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT event_type, COUNT(*) AS total
                 FROM suspicious_events
                 WHERE created_at >= ?
                 GROUP BY event_type
                 ORDER BY total DESC'
            );
            $stmt->execute([$since]);
        } else {
            $stmt = $this->pdo->query(
                'SELECT event_type, COUNT(*) AS total
                 FROM suspicious_events
                 GROUP BY event_type
                 ORDER BY total DESC'
            );
        }

        return $stmt->fetchAll();
    }

    public function getRecentCriticalSessionEvents(int $limit = 20, ?string $since = null): array
    {
        $types = [
            'session_hijack_suspected',
            'session_guard_missing',
            'session_guard_mismatch',
            'session_context_missing',
        ];

        $placeholders = implode(', ', array_fill(0, count($types), '?'));

        // Build query that prefers the explicit email on the event but falls back to the user's email
        if ($since !== null) {
            $sql =
                'SELECT se.*, COALESCE(se.email, u.email) AS email
                 FROM suspicious_events se
                 LEFT JOIN users u ON u.id = se.user_id
                 WHERE se.event_type IN (' . $placeholders . ')
                   AND se.created_at >= ?
                 ORDER BY se.created_at DESC
                 LIMIT ?';

            $stmt = $this->pdo->prepare($sql);
            $index = 1;

            foreach ($types as $type) {
                $stmt->bindValue($index++, $type, PDO::PARAM_STR);
            }

            $stmt->bindValue($index++, $since, PDO::PARAM_STR);
            $stmt->bindValue($index, $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $sql =
            'SELECT se.*, COALESCE(se.email, u.email) AS email
             FROM suspicious_events se
             LEFT JOIN users u ON u.id = se.user_id
             WHERE se.event_type IN (' . $placeholders . ')
             ORDER BY se.created_at DESC
             LIMIT ?';

        $stmt = $this->pdo->prepare($sql);
        $index = 1;

        foreach ($types as $type) {
            $stmt->bindValue($index++, $type, PDO::PARAM_STR);
        }

        $stmt->bindValue($index, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRecentCardChecks(int $limit = 20, ?string $since = null): array
    {
        if ($since !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT ccr.*, u.email, p.name AS project_name
                 FROM card_check_requests ccr
                 LEFT JOIN users u ON u.id = ccr.user_id
                 LEFT JOIN projects p ON p.id = ccr.project_id
                 WHERE ccr.checked_at >= ?
                 ORDER BY ccr.checked_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $since, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT ccr.*, u.email, p.name AS project_name
                 FROM card_check_requests ccr
                 LEFT JOIN users u ON u.id = ccr.user_id
                 LEFT JOIN projects p ON p.id = ccr.project_id
                 ORDER BY ccr.checked_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getBlockedIps(int $limit = 20, ?string $since = null): array
    {
        if ($since !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT *
                 FROM blocked_ips
                 WHERE created_at >= ?
                 ORDER BY created_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $since, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT *
                 FROM blocked_ips
                 ORDER BY created_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getRecentRequests(int $limit = 30, ?string $since = null): array
    {
        if ($since !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT *
                 FROM request_logs
                 WHERE created_at >= ?
                 ORDER BY created_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $since, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT *
                 FROM request_logs
                 ORDER BY created_at DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getTopCountries(int $limit = 10, ?string $since = null): array
    {
        if ($since !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT country, COUNT(*) AS total
                 FROM request_logs
                 WHERE created_at >= ?
                 GROUP BY country
                 ORDER BY total DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $since, PDO::PARAM_STR);
            $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT country, COUNT(*) AS total
                 FROM request_logs
                 GROUP BY country
                 ORDER BY total DESC
                 LIMIT ?'
            );
            $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }
}