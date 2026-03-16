<?php

class AdminDashboard
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function getCounts(): array
    {
        return [
            'users' => $this->countTable('users'),
            'projects' => $this->countTable('projects'),
            'card_checks' => $this->countTable('card_check_requests'),
            'audit_logs' => $this->countTable('audit_logs'),
            'login_attempts' => $this->countTable('login_attempts'),
            'suspicious_events' => $this->countTable('suspicious_events'),
            'password_resets' => $this->countTable('password_resets'),
            'blocked_ips' => $this->countTable('blocked_ips'),
            'request_logs' => $this->countTable('request_logs'),
        ];
    }

    private function countTable(string $table): int
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

        $stmt = $this->pdo->query("SELECT COUNT(*) AS total FROM {$table}");
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }

    public function getRecentAuditLogs(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT al.*, u.email
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             ORDER BY al.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getRecentSuspiciousEvents(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT se.*
             FROM suspicious_events se
             ORDER BY se.created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getRecentLoginAttempts(int $limit = 30): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM login_attempts
             ORDER BY attempted_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getTopIps(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ip_address, COUNT(*) AS total
             FROM login_attempts
             GROUP BY ip_address
             ORDER BY total DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getSuspiciousEventTypes(): array
    {
        $stmt = $this->pdo->query(
            'SELECT event_type, COUNT(*) AS total
             FROM suspicious_events
             GROUP BY event_type
             ORDER BY total DESC'
        );

        return $stmt->fetchAll();
    }

    public function getRecentCardChecks(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ccr.*, u.email, p.name AS project_name
             FROM card_check_requests ccr
             LEFT JOIN users u ON u.id = ccr.user_id
             LEFT JOIN projects p ON p.id = ccr.project_id
             ORDER BY ccr.checked_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getBlockedIps(int $limit = 20): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM blocked_ips
             ORDER BY created_at DESC
             LIMIT ?'
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getRecentRequests(int $limit = 30): array
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
}