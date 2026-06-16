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

    /**
     * Campos pessoais opcionais que o usuário pode apagar individualmente (LGPD).
     * Espelha db_user_encrypted_columns() menos os obrigatórios — 'name' é
     * NOT NULL e não pode ser zerado. Todos os demais são colunas NULL-áveis.
     *
     * @return string[]
     */
    public function removableFields(): array
    {
        $columns = array_keys(db_user_encrypted_columns());

        return array_values(array_filter(
            $columns,
            static fn (string $col): bool => $col !== 'name'
        ));
    }

    /**
     * Zera (NULL) um único campo pessoal do usuário. O nome da coluna é validado
     * contra a whitelist removableFields() antes de entrar na query — nunca vem
     * direto da entrada do usuário, então a interpolação é segura.
     */
    public function clearUserField(int $userId, string $field): bool
    {
        if (!in_array($field, $this->removableFields(), true)) {
            return false;
        }

        $stmt = $this->pdo->prepare("UPDATE users SET {$field} = NULL WHERE id = ?");

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
            'SELECT *
             FROM users
             WHERE id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            return null;
        }

        // Decifra os campos sensíveis (S.3.2) — esta query não passa pelo model User.
        foreach (db_user_encrypted_columns() as $field => $meta) {
            if (array_key_exists($field, $user) && $user[$field] !== null) {
                $user[$field] = DbCipher::decrypt((string) $user[$field]);
            }
        }

        return $user;
    }
}