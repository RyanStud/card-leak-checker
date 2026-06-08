<?php

/**
 * Backfill da criptografia do banco (S.3.2) — alarga as colunas e cifra os
 * registros JÁ existentes sem perder dados. Idempotente: pula valores já
 * cifrados; pode rodar quantas vezes quiser.
 *
 * Uso:
 *   php scripts/encrypt-db.php            (aplica)
 *   php scripts/encrypt-db.php --dry-run  (só mostra o que faria)
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Execute via CLI.\n");
    exit(1);
}

$dryRun = in_array('--dry-run', $argv, true);

require __DIR__ . '/../app/core/Env.php';
Env::load(__DIR__ . '/../.env');
require __DIR__ . '/../app/core/SecretManager.php';
require __DIR__ . '/../app/core/Config.php';
require __DIR__ . '/../app/helpers/env.php';
require __DIR__ . '/../app/helpers/hybrid_crypto.php';
require __DIR__ . '/../app/core/DbCipher.php';
require __DIR__ . '/../app/core/Database.php';
require __DIR__ . '/../app/helpers/db_migrate.php';

Config::init();

try {
    $pdo = Database::getConnection();

    // Garante a DB_ENC_KEY no cofre (gera+armazena se faltar) e a injeta.
    $master = Config::masterKey();
    $secretsEnc = dirname(__DIR__) . '/' . ltrim((string) Env::get('SECRETS_FILE', 'config/secrets.enc'), '/');
    DbCipher::ensureVaultKeyMaterial($master, $secretsEnc, dirname(__DIR__));
} catch (\Throwable $e) {
    error_log('encrypt-db init: ' . $e->getMessage());
    fwrite(STDERR, "Erro de inicialização (detalhe no log de erros).\n");
    exit(1);
}

echo "=== Backfill de criptografia do banco" . ($dryRun ? ' (DRY-RUN)' : '') . " ===\n";

if ($dryRun) {
    $cols = db_user_encrypted_columns();
    $fields = array_keys($cols);
    $rows = $pdo->query('SELECT id, ' . implode(', ', $fields) . ' FROM users')->fetchAll(PDO::FETCH_ASSOC);

    $wouldUpdate = 0;
    foreach ($rows as $row) {
        foreach ($fields as $field) {
            $value = $row[$field] ?? null;
            if ($value !== null && $value !== '' && !DbCipher::isEncrypted((string) $value)) {
                $wouldUpdate++;
                break;
            }
        }
    }

    echo 'Usuários: ' . count($rows) . " | seriam cifrados: {$wouldUpdate}\n";
    exit(0);
}

db_widen_user_columns($pdo);
$updated = db_backfill_user_encryption($pdo);

echo "Backfill concluído: {$updated} usuário(s) cifrado(s).\n";
DbCipher::console('[S.3.2] Backfill concluído: ' . $updated . ' registro(s) cifrado(s).');
