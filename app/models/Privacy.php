<?php

class Privacy
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function deleteUserHistory(int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM card_check_requests WHERE user_id = ?');
        return $stmt->execute([$userId]);
    }

    public function deleteOwnedProjects(int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM projects WHERE owner_user_id = ?');
        return $stmt->execute([$userId]);
    }

    public function anonymizeAuditLogs(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE audit_logs
             SET metadata = ?, action_name = ?
             WHERE user_id = ?"
        );

        return $stmt->execute([
            'anonimizado por solicitação LGPD',
            'lgpd_anonymized',
            $userId
        ]);
    }

    public function deleteUserAccount(int $userId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$userId]);
    }

    public function countUserHistory(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM card_check_requests WHERE user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }

    public function countOwnedProjects(int $userId): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM projects WHERE owner_user_id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }

    public function getUserProfileSummary(int $userId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, email_verified, two_factor_enabled, created_at
             FROM users
             WHERE id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        return $user ?: null;
    }
}