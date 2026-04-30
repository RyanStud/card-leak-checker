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
        $currentUser = $this->findById($userId);
        $currentHash = $currentUser['password_hash'] ?? null;

        try {
            $this->pdo->beginTransaction();

            if (is_string($currentHash) && $currentHash !== '') {
                $historyStmt = $this->pdo->prepare(
                    'INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)'
                );
                $historyStmt->execute([$userId, $currentHash]);
            }

            $stmt = $this->pdo->prepare(
                'UPDATE users SET password_hash = ? WHERE id = ?'
            );
            $updated = $stmt->execute([$passwordHash, $userId]);

            $this->prunePasswordHistory($userId);
            $this->pdo->commit();

            return $updated;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }

    public function hasRecentlyUsedPassword(int $userId, string $plainPassword, int $limit = 5): bool
    {
        $user = $this->findById($userId);
        if ($user && password_verify($plainPassword, (string)$user['password_hash'])) {
            return true;
        }

        $limit = max(1, min($limit, 20));
        $stmt = $this->pdo->prepare(
            'SELECT password_hash
             FROM password_history
             WHERE user_id = ?
             ORDER BY changed_at DESC, id DESC
             LIMIT ' . (int)$limit
        );
        $stmt->execute([$userId]);

        foreach ($stmt->fetchAll() as $historyRow) {
            if (password_verify($plainPassword, (string)$historyRow['password_hash'])) {
                return true;
            }
        }

        return false;
    }

    private function prunePasswordHistory(int $userId, int $limit = 5): void
    {
        $limit = max(1, min($limit, 20));
        $stmt = $this->pdo->prepare(
            'DELETE FROM password_history
             WHERE user_id = ?
               AND id NOT IN (
                   SELECT id FROM (
                       SELECT id
                       FROM password_history
                       WHERE user_id = ?
                       ORDER BY changed_at DESC, id DESC
                       LIMIT ' . (int)$limit . '
                   ) recent_passwords
               )'
        );
        $stmt->execute([$userId, $userId]);
    }

    public function findByCpf(string $cpfDigits): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE cpf = ? LIMIT 1');
        $stmt->execute([$cpfDigits]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function updateProfile(
        int $userId,
        string $name,
        ?string $cpf,
        ?string $jobTitle,
        ?string $cep,
        ?string $addressStreet,
        ?string $addressNumber,
        ?string $addressComplement,
        ?string $addressNeighborhood,
        ?string $addressCity,
        ?string $addressState
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET name = ?,
                 cpf = ?,
                 job_title = ?,
                 cep = ?,
                 address_street = ?,
                 address_number = ?,
                 address_complement = ?,
                 address_neighborhood = ?,
                 address_city = ?,
                 address_state = ?
             WHERE id = ?'
        );

        return $stmt->execute([
            $name,
            $cpf,
            $jobTitle,
            $cep,
            $addressStreet,
            $addressNumber,
            $addressComplement,
            $addressNeighborhood,
            $addressCity,
            $addressState,
            $userId,
        ]);
    }

    public function getAllUsers(?string $since = null): array
    {
        if ($since !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT id, name, email, role, email_verified, two_factor_enabled, created_at
                 FROM users
                 WHERE created_at >= ?
                 ORDER BY created_at DESC'
            );
            $stmt->execute([$since]);
        } else {
            $stmt = $this->pdo->query(
                'SELECT id, name, email, role, email_verified, two_factor_enabled, created_at
                 FROM users
                 ORDER BY created_at DESC'
            );
        }

        return $stmt->fetchAll();
    }
}
