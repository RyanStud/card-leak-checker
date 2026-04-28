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

    public function getUsersWithTelegramStatus(string $emailFilter = '', int $limit = 20, int $offset = 0): array
    {
        $sql = 'SELECT
                    u.id,
                    u.name,
                    u.email,
                    u.role,
                    u.email_verified,
                    u.two_factor_enabled,
                    u.created_at,
                    ta.telegram_user_id,
                    ta.telegram_username,
                    ta.telegram_phone,
                    ta.is_active AS telegram_is_active
                FROM users u
                LEFT JOIN telegram_accounts ta ON ta.user_id = u.id';

        $params = [];
        if ($emailFilter !== '') {
            $sql .= ' WHERE u.email LIKE ?';
            $params[] = '%' . $emailFilter . '%';
        }

        $sql .= ' ORDER BY u.created_at DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $index => $value) {
            $paramId = $index + 1;
            if (is_int($value)) {
                $stmt->bindValue($paramId, $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($paramId, $value, PDO::PARAM_STR);
            }
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countUsersWithTelegramStatus(string $emailFilter = ''): int
    {
        $sql = 'SELECT COUNT(*) AS total FROM users';
        $params = [];

        if ($emailFilter !== '') {
            $sql .= ' WHERE email LIKE ?';
            $params[] = '%' . $emailFilter . '%';
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }

    public function updateRole(int $userId, string $role): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        return $stmt->execute([$role, $userId]);
    }

    public function countAdmins(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
        $row = $stmt->fetch();

        return (int)($row['total'] ?? 0);
    }
}
