<?php

/**
 * Migração da criptografia do banco (S.3.2) — fonte única de verdade dos campos
 * cifrados, usada pelo setup, pelo backfill e pelo deploy sem terminal.
 */

/**
 * Colunas sensíveis da tabela users: coluna => [tipo alargado, determinístico?].
 * email NÃO entra (fica em claro). cpf é determinístico (UNIQUE/findByCpf).
 *
 * @return array<string,array{0:string,1:bool}>
 */
function db_user_encrypted_columns(): array
{
    return [
        'name' => ['VARCHAR(512) NOT NULL', false],
        'cpf' => ['VARCHAR(255) NULL', true],
        'job_title' => ['VARCHAR(512) NULL', false],
        'cep' => ['VARCHAR(255) NULL', false],
        'address_street' => ['VARCHAR(512) NULL', false],
        'address_number' => ['VARCHAR(255) NULL', false],
        'address_complement' => ['VARCHAR(512) NULL', false],
        'address_neighborhood' => ['VARCHAR(512) NULL', false],
        'address_city' => ['VARCHAR(512) NULL', false],
        'address_state' => ['VARCHAR(255) NULL', false],
    ];
}

/**
 * Alarga as colunas para caberem os ciphertexts. Idempotente e não-destrutivo;
 * ignora colunas ausentes ou já no formato.
 */
function db_widen_user_columns(PDO $pdo): void
{
    foreach (db_user_encrypted_columns() as $col => [$type]) {
        try {
            $pdo->exec("ALTER TABLE users MODIFY {$col} {$type}");
        } catch (\Throwable $e) {
            // coluna ausente / já alargada — segue
        }
    }
}

/**
 * Cifra os registros ainda em claro. Idempotente (pula os já cifrados e os
 * vazios). Retorna quantos usuários foram atualizados.
 */
function db_backfill_user_encryption(PDO $pdo): int
{
    $cols = db_user_encrypted_columns();
    $fields = array_keys($cols);

    $rows = $pdo->query('SELECT id, ' . implode(', ', $fields) . ' FROM users')->fetchAll(PDO::FETCH_ASSOC);

    $updated = 0;
    foreach ($rows as $row) {
        $sets = [];
        $params = [];

        foreach ($cols as $field => [$type, $deterministic]) {
            $value = $row[$field] ?? null;
            if ($value === null || $value === '' || DbCipher::isEncrypted((string) $value)) {
                continue;
            }
            $sets[] = "{$field} = ?";
            $params[] = DbCipher::encrypt((string) $value, $deterministic);
        }

        if ($sets === []) {
            continue;
        }

        $params[] = (int) $row['id'];
        $pdo->prepare('UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
        $updated++;
    }

    return $updated;
}
