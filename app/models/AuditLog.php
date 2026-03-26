<?php

class AuditLog
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function create(int $userId, ?int $projectId, string $actionName, ?string $metadata = null): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_logs (user_id, project_id, action_name, metadata)
             VALUES (?, ?, ?, ?)'
        );

        return $stmt->execute([$userId, $projectId, $actionName, $metadata]);
    }
}