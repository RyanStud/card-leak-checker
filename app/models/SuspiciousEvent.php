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
}