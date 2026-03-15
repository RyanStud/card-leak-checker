<?php

class User
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function create(string $name, string $email, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, email_verified) VALUES (?, ?, ?, 0)'
        );

        return $stmt->execute([$name, $email, $passwordHash]);
    }

    public function markEmailAsVerified(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET email_verified = 1 WHERE id = ?'
        );

        return $stmt->execute([$userId]);
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function saveTwoFactorSecret(int $userId, string $secret): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?'
        );

        return $stmt->execute([$secret, $userId]);
    }

    public function updatePassword(int $userId, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_hash = ? WHERE id = ?'
        );

        return $stmt->execute([$passwordHash, $userId]);
    }

    public function getAllUsers(): array
    {
        $stmt = $this->pdo->query(
            'SELECT id, name, email, role, email_verified, two_factor_enabled, created_at
             FROM users
             ORDER BY created_at DESC'
        );

        return $stmt->fetchAll();
    }
}
