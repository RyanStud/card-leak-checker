<?php

class TelegramAccount
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function findByUserId(int $userId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM telegram_accounts WHERE user_id = ? LIMIT 1');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findByTelegramUserId(int $telegramUserId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM telegram_accounts WHERE telegram_user_id = ? LIMIT 1');
        $stmt->execute([$telegramUserId]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function findPendingByCode(string $plainCode): ?array
    {
        $hash = hash('sha256', $plainCode);

        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM telegram_accounts
             WHERE link_code_hash = ?
               AND link_code_expires_at IS NOT NULL
               AND link_code_expires_at >= NOW()
             LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();

        return $row ?: null;
    }

    public function createOrRefreshPendingLink(int $userId, string $plainCode, string $expiresAt): bool
    {
        $hash = hash('sha256', $plainCode);

        $stmt = $this->pdo->prepare(
            'INSERT INTO telegram_accounts (
                user_id,
                link_code_hash,
                link_code_expires_at,
                is_active,
                notifications_enabled,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, 0, 1, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                link_code_hash = VALUES(link_code_hash),
                link_code_expires_at = VALUES(link_code_expires_at),
                is_active = IF(telegram_user_id IS NULL, 0, is_active),
                updated_at = NOW()'
        );

        return $stmt->execute([$userId, $hash, $expiresAt]);
    }

    public function saveUserPreferences(
        int $userId,
        ?string $telegramUsername,
        ?string $telegramPhone,
        int $notificationsEnabled
    ): bool {
        $stmt = $this->pdo->prepare(
            'INSERT INTO telegram_accounts (
                user_id,
                telegram_username,
                telegram_phone,
                notifications_enabled,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                telegram_username = VALUES(telegram_username),
                telegram_phone = VALUES(telegram_phone),
                notifications_enabled = VALUES(notifications_enabled),
                updated_at = NOW()'
        );

        return $stmt->execute([
            $userId,
            $telegramUsername,
            $telegramPhone,
            $notificationsEnabled,
        ]);
    }

    public function completeLink(
        int $id,
        int $telegramUserId,
        ?string $telegramUsername,
        ?string $telegramFirstName,
        ?string $telegramLastName,
        ?string $telegramPhone
    ): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE telegram_accounts
             SET telegram_user_id = ?,
                 telegram_username = ?,
                 telegram_first_name = ?,
                 telegram_last_name = ?,
                 telegram_phone = COALESCE(?, telegram_phone),
                 is_active = 1,
                 linked_at = NOW(),
                 last_interaction_at = NOW(),
                 link_code_hash = NULL,
                 link_code_expires_at = NULL,
                 updated_at = NOW()
             WHERE id = ?'
        );

        return $stmt->execute([
            $telegramUserId,
            $telegramUsername,
            $telegramFirstName,
            $telegramLastName,
            $telegramPhone,
            $id,
        ]);
    }

    public function touchInteractionByTelegramUserId(int $telegramUserId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE telegram_accounts
             SET last_interaction_at = NOW(),
                 updated_at = NOW()
             WHERE telegram_user_id = ?'
        );

        return $stmt->execute([$telegramUserId]);
    }

    public function savePhoneByTelegramUserId(int $telegramUserId, string $telegramPhone): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE telegram_accounts
             SET telegram_phone = ?,
                 last_interaction_at = NOW(),
                 updated_at = NOW()
             WHERE telegram_user_id = ?'
        );

        return $stmt->execute([$telegramPhone, $telegramUserId]);
    }

    public function issueLoginCode(int $userId, string $plainCode, string $expiresAt): bool
    {
        $hash = hash('sha256', $plainCode);

        $stmt = $this->pdo->prepare(
            'UPDATE telegram_accounts
             SET login_code_hash = ?,
                 login_code_expires_at = ?,
                 login_code_sent_at = NOW(),
                 updated_at = NOW()
             WHERE user_id = ?
               AND telegram_user_id IS NOT NULL
               AND is_active = 1'
        );

        return $stmt->execute([$hash, $expiresAt, $userId]);
    }

    public function consumeValidLoginCode(int $userId, string $plainCode): bool
    {
        $hash = hash('sha256', $plainCode);

        $stmt = $this->pdo->prepare(
            'SELECT id
             FROM telegram_accounts
             WHERE user_id = ?
               AND login_code_hash = ?
               AND login_code_expires_at IS NOT NULL
               AND login_code_expires_at >= NOW()
               AND is_active = 1
             LIMIT 1'
        );
        $stmt->execute([$userId, $hash]);
        $row = $stmt->fetch();

        if (!$row) {
            return false;
        }

        $clearStmt = $this->pdo->prepare(
            'UPDATE telegram_accounts
             SET login_code_hash = NULL,
                 login_code_expires_at = NULL,
                 updated_at = NOW()
             WHERE id = ?'
        );

        $clearStmt->execute([(int)$row['id']]);
        return true;
    }

    public function unlinkByUserId(int $userId): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE telegram_accounts
             SET telegram_user_id = NULL,
                 is_active = 0,
                 linked_at = NULL,
                 link_code_hash = NULL,
                 link_code_expires_at = NULL,
                 updated_at = NOW()
             WHERE user_id = ?'
        );

        return $stmt->execute([$userId]);
    }

    public function getNoticeRecipients(bool $onlyAdmins = false): array
    {
        $sql = 'SELECT
                    ta.user_id,
                    ta.telegram_user_id,
                    ta.telegram_username,
                    u.email,
                    u.role
                FROM telegram_accounts ta
                INNER JOIN users u ON u.id = ta.user_id
                WHERE ta.is_active = 1
                  AND ta.notifications_enabled = 1
                  AND ta.telegram_user_id IS NOT NULL';

        if ($onlyAdmins) {
            $sql .= " AND u.role = 'admin'";
        }

        $sql .= ' ORDER BY ta.user_id ASC';

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }
}
