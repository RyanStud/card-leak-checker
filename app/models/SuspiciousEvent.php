<?php

class SuspiciousEvent
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function create(?int $userId, ?string $email, string $ipAddress, string $eventType, ?string $details = null): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO suspicious_events (user_id, email, ip_address, event_type, details)
             VALUES (?, ?, ?, ?, ?)'
        );

        return $stmt->execute([$userId, $email, $ipAddress, $eventType, $details]);
    }

    public function countRecentByIpAndType(string $ipAddress, string $eventType, int $minutes = 15): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) AS total
             FROM suspicious_events
             WHERE ip_address = ?
               AND event_type = ?
               AND created_at >= (NOW() - INTERVAL ? MINUTE)'
        );
        $stmt->execute([$ipAddress, $eventType, $minutes]);
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }
}