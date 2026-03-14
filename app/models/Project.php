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