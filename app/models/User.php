<?php

class User
{
    private PDO $pdo;

    /** Campos sensíveis cifrados com IV aleatório (só exibição). */
    private const ENC_FIELDS = [
        'name',
        'job_title',
        'cep',
        'address_street',
        'address_number',
        'address_complement',
        'address_neighborhood',
        'address_city',
        'address_state',
    ];

    /** Campos cifrados de forma determinística (precisam de igualdade/UNIQUE). */
    private const ENC_FIELDS_DETERMINISTIC = ['cpf'];

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    /**
     * Decifra os campos sensíveis de uma linha lida do banco (S.3.2).
     * email/password_hash/role não são cifrados.
     */
    private function decryptRow(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        foreach (array_merge(self::ENC_FIELDS, self::ENC_FIELDS_DETERMINISTIC) as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null) {
                $row[$field] = DbCipher::decrypt((string) $row[$field]);
            }
        }

        return $row;
    }

    /** @param array<int,array<string,mixed>> $rows */
    private function decryptRows(array $rows): array
    {
        return array_map(fn ($row) => $this->decryptRow($row), $rows);
    }

    public function create(string $name, string $email, string $passwordHash): bool
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, email_verified) VALUES (?, ?, ?, 0)'
        );

        // S.3.2.c - dado sensível do form cifrado antes de ir ao banco.
        return $stmt->execute([DbCipher::encrypt($name), $email, $passwordHash]);
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
        // email fica em claro (não cifrado) -> busca direta.
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        return $user ? $this->decryptRow($user) : null;
    }

    /**
     * Linha CRUA (ainda cifrada) por email — usada para demonstrar o S.3.2.d
     * (recuperar do BD cifrado e depois decifrar).
     */
    public function findRawByEmail(string $email): ?array
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

        return $user ? $this->decryptRow($user) : null;
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
        // cpf é cifrado de forma determinística -> cifra o termo de busca para casar.
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE cpf = ? LIMIT 1');
        $stmt->execute([DbCipher::encrypt($cpfDigits, true)]);
        $user = $stmt->fetch();

        return $user ? $this->decryptRow($user) : null;
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
            DbCipher::encrypt($name),
            $cpf !== null ? DbCipher::encrypt($cpf, true) : null,
            DbCipher::encrypt($jobTitle),
            DbCipher::encrypt($cep),
            DbCipher::encrypt($addressStreet),
            DbCipher::encrypt($addressNumber),
            DbCipher::encrypt($addressComplement),
            DbCipher::encrypt($addressNeighborhood),
            DbCipher::encrypt($addressCity),
            DbCipher::encrypt($addressState),
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

        return $this->decryptRows($stmt->fetchAll());
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

        return $this->decryptRows($stmt->fetchAll());
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
