<?php

class CardCheckRequest
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function create(
        int $userId,
        int $projectId,
        string $cardFingerprint,
        string $binMasked,
        string $last4Masked,
        string $resultStatus,
        string $sourceName = 'demo-local'
    ): bool {
        $stmt = $this->pdo->prepare(
            'INSERT INTO card_check_requests
            (user_id, project_id, card_fingerprint, bin_masked, last4_masked, result_status, source_name)
            VALUES (?, ?, ?, ?, ?, ?, ?)'
        );

        return $stmt->execute([
            $userId,
            $projectId,
            $cardFingerprint,
            $binMasked,
            $last4Masked,
            $resultStatus,
            $sourceName
        ]);
    }

    public function getByUserProjects(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT ccr.*, p.name AS project_name
             FROM card_check_requests ccr
             INNER JOIN projects p ON p.id = ccr.project_id
             INNER JOIN project_memberships pm ON pm.project_id = p.id
             WHERE pm.user_id = ?
             ORDER BY ccr.checked_at DESC'
        );

        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}