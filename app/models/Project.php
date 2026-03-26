<?php

class Project
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function create(string $name, string $slug, int $ownerUserId, string $privacyMode = 'private'): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO projects (name, slug, owner_user_id, privacy_mode) VALUES (?, ?, ?, ?)'
        );

        $created = $stmt->execute([$name, $slug, $ownerUserId, $privacyMode]);

        if ($created) {
            $projectId = (int)$this->pdo->lastInsertId();
            $this->addMember($projectId, $ownerUserId, 'owner');
        }

        return $created;
    }

    public function createWithJustification(string $name, string $slug, int $ownerUserId, string $privacyMode, string $justification): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO projects (name, slug, owner_user_id, privacy_mode, justification, approval_status) 
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        $created = $stmt->execute([$name, $slug, $ownerUserId, $privacyMode, $justification, 'pending']);

        if ($created) {
            $projectId = (int)$this->pdo->lastInsertId();
            $this->addMember($projectId, $ownerUserId, 'owner');
        }

        return $created;
    }

    public function getPendingProjects(): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, u.email, u.name 
             FROM projects p
             INNER JOIN users u ON u.id = p.owner_user_id
             WHERE p.approval_status = ?
             ORDER BY p.created_at ASC'
        );

        $stmt->execute(['pending']);
        return $stmt->fetchAll();
    }

    public function getApprovalHistory(int $limit = 50, int $offset = 0): array
    {
        $limit = max(1, min($limit, 1000));
        $offset = max(0, $offset);

        $stmt = $this->pdo->prepare(
            'SELECT 
                p.id,
                p.name,
                p.approval_status,
                p.justification,
                p.created_at as project_created_at,
                p.approved_at,
                p.rejection_reason,
                u_owner.name as owner_name,
                u_owner.email as owner_email,
                u_admin.name as admin_name,
                u_admin.email as admin_email
             FROM projects p
             INNER JOIN users u_owner ON u_owner.id = p.owner_user_id
             LEFT JOIN users u_admin ON u_admin.id = p.approved_by
             WHERE p.approval_status IN (?, ?)
             ORDER BY COALESCE(p.approved_at, p.created_at) DESC
             LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset
        );

        $stmt->execute(['approved', 'rejected']);
        return $stmt->fetchAll();
    }

    public function getApprovalHistoryCount(): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) as total FROM projects WHERE approval_status IN (?, ?)'
        );

        $stmt->execute(['approved', 'rejected']);
        $result = $stmt->fetch();

        return (int)($result['total'] ?? 0);
    }

    public function approveProject(int $projectId, int $approvedByUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE projects 
             SET approval_status = ?, approved_at = NOW(), approved_by = ?
             WHERE id = ?'
        );

        return $stmt->execute(['approved', $approvedByUserId, $projectId]);
    }

    public function rejectProject(int $projectId, int $rejectedByUserId, string $rejectionReason = ''): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE projects 
             SET approval_status = ?, approved_at = NOW(), approved_by = ?, rejection_reason = ?
             WHERE id = ?'
        );

        return $stmt->execute(['rejected', $rejectedByUserId, $rejectionReason, $projectId]);
    }

    public function addMember(int $projectId, int $userId, string $role = 'member'): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO project_memberships (project_id, user_id, role) VALUES (?, ?, ?)'
        );

        return $stmt->execute([$projectId, $userId, $role]);
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM projects WHERE slug = ? LIMIT 1');
        $stmt->execute([$slug]);
        $project = $stmt->fetch();

        return $project ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM projects WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $project = $stmt->fetch();

        return $project ?: null;
    }

    public function getProjectsByUserId(int $userId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, pm.role
             FROM projects p
             INNER JOIN project_memberships pm ON pm.project_id = p.id
             WHERE pm.user_id = ?
             ORDER BY p.created_at DESC'
        );

        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function userHasAccess(int $projectId, int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT id FROM project_memberships WHERE project_id = ? AND user_id = ? LIMIT 1'
        );
        $stmt->execute([$projectId, $userId]);

        return (bool)$stmt->fetch();
    }
}