<?php

class LoginAttempt
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function create(?string $email, string $ipAddress, bool $success): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, ?)'
        );

        return $stmt->execute([$email, $ipAddress, $success ? 1 : 0]);
    }

    public function countRecentFailedByIp(string $ipAddress, int $minutes = 15): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM login_attempts
             WHERE ip_address = ?
               AND success = 0
               AND attempted_at >= (NOW() - INTERVAL ? MINUTE)'
        );

        $stmt->execute([$ipAddress, $minutes]);
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }

    public function countRecentFailedByEmail(string $email, int $minutes = 15): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM login_attempts
             WHERE email = ?
               AND success = 0
               AND attempted_at >= (NOW() - INTERVAL ? MINUTE)'
        );

        $stmt->execute([$email, $minutes]);
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }

    public function countDistinctEmailsByIp(string $ipAddress, int $minutes = 15): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(DISTINCT email) AS total
             FROM login_attempts
             WHERE ip_address = ?
               AND email IS NOT NULL
               AND attempted_at >= (NOW() - INTERVAL ? MINUTE)'
        );

        $stmt->execute([$ipAddress, $minutes]);
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }
}