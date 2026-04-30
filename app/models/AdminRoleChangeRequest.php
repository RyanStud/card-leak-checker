<?php

class AdminRoleChangeRequest
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function hasPendingForUser(int $targetUserId): bool
    {
        $stmt = $this->pdo->prepare(
            "SELECT id
             FROM admin_role_change_requests
             WHERE target_user_id = ?
               AND status = 'pending'
             LIMIT 1"
        );
        $stmt->execute([$targetUserId]);

        return (bool)$stmt->fetch();
    }

    public function createRequest(int $requestedBy, int $targetUserId, string $currentRole, string $requestedRole): bool
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO admin_role_change_requests (
                requested_by_user_id,
                target_user_id,
                from_role,
                to_role,
                status,
                created_at,
                updated_at
             ) VALUES (?, ?, ?, ?, 'pending', NOW(), NOW())"
        );

        return $stmt->execute([$requestedBy, $targetUserId, $currentRole, $requestedRole]);
    }

    public function getPending(int $limit = 30): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                r.id,
                r.requested_by_user_id,
                r.target_user_id,
                r.from_role,
                r.to_role,
                r.status,
                r.created_at,
                req.email AS requester_email,
                req.name AS requester_name,
                target.email AS target_email,
                target.name AS target_name
             FROM admin_role_change_requests r
             INNER JOIN users req ON req.id = r.requested_by_user_id
             INNER JOIN users target ON target.id = r.target_user_id
             WHERE r.status = 'pending'
             ORDER BY r.created_at ASC
             LIMIT ?"
        );
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function findPendingById(int $requestId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
             FROM admin_role_change_requests
             WHERE id = ?
               AND status = 'pending'
             LIMIT 1"
        );
        $stmt->execute([$requestId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function markApproved(int $requestId, int $reviewedBy): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE admin_role_change_requests
             SET status = 'approved',
                 reviewed_by_user_id = ?,
                 reviewed_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?
               AND status = 'pending'"
        );

        return $stmt->execute([$reviewedBy, $requestId]);
    }

    public function markRejected(int $requestId, int $reviewedBy): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE admin_role_change_requests
             SET status = 'rejected',
                 reviewed_by_user_id = ?,
                 reviewed_at = NOW(),
                 updated_at = NOW()
             WHERE id = ?
               AND status = 'pending'"
        );

        return $stmt->execute([$reviewedBy, $requestId]);
    }
}
