<?php

class PasswordReset
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function create(int $userId, string $email, string $tokenHash, string $expiresAt): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO password_resets (user_id, email, token_hash, expires_at)
             VALUES (?, ?, ?, ?)'
        );

        return $stmt->execute([$userId, $email, $tokenHash, $expiresAt]);
    }

    public function findValidByToken(string $plainToken): ?array
    {
        $tokenHash = hash('sha256', $plainToken);

        $stmt = $this->pdo->prepare(
            'SELECT * FROM password_resets
             WHERE token_hash = ?
               AND used_at IS NULL
               AND expires_at >= NOW()
             ORDER BY id DESC
             LIMIT 1'
        );
        $stmt->execute([$tokenHash]);
        $reset = $stmt->fetch();

        return $reset ?: null;
    }

    public function markAsUsed(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE password_resets SET used_at = NOW() WHERE id = ?'
        );

        return $stmt->execute([$id]);
    }

    public function invalidateAllByUser(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE password_resets SET used_at = NOW()
             WHERE user_id = ? AND used_at IS NULL'
        );

        return $stmt->execute([$userId]);
    }
}