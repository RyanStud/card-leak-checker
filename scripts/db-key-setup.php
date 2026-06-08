<?php

/**
 * S.3.2.a / S.3.2.b — Garante a chave simétrica do banco (DB_ENC_KEY) DENTRO da
 * gestão de segredos (config/secrets.enc). Use para os screenshots da chave
 * gerada (a) e da operação de armazenamento no cofre (b).
 *
 * Uso: php scripts/db-key-setup.php
 *
 * Não regenera a chave se ela já existir (regerar tornaria os dados já cifrados
 * irrecuperáveis).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Execute via CLI.\n");
    exit(1);
}

require __DIR__ . '/../app/core/Env.php';
Env::load(__DIR__ . '/../.env');
require __DIR__ . '/../app/core/SecretManager.php';
require __DIR__ . '/../app/core/Config.php';
require __DIR__ . '/../app/helpers/env.php';
require __DIR__ . '/../app/helpers/hybrid_crypto.php';
require __DIR__ . '/../app/core/DbCipher.php';

Config::init();

$master = Config::masterKey();
if ($master === '') {
    fwrite(STDERR, "SECRET_MASTER_KEY ausente. Rode 'composer setup' antes.\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);
$secretsEnc = $projectRoot . '/' . ltrim((string) Env::get('SECRETS_FILE', 'config/secrets.enc'), '/');

echo "=== S.3.2 :: Chave de criptografia do banco no cofre de segredos ===\n";

if (!is_file($secretsEnc)) {
    fwrite(STDERR, "Cofre {$secretsEnc} não existe. Rode 'php generate-secrets.php' (a partir do config/secrets.json) antes.\n");
    exit(1);
}

try {
    $vault = new SecretManager($secretsEnc, $master);
    $jaTinha = trim((string) $vault->get('DB_ENC_KEY', '')) !== '';

    DbCipher::ensureVaultKeyMaterial($master, $secretsEnc, $projectRoot);

    if ($jaTinha) {
        echo "[ok] DB_ENC_KEY já existia no cofre (não regenerada).\n";
    } else {
        echo "[ok] DB_ENC_KEY gerada e armazenada no cofre. Veja [S.3.2.a]/[S.3.2.b] acima\n";
        echo "     (e em storage/logs/db-crypto.log) para os screenshots.\n";
    }
} catch (\Throwable $e) {
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "\nLEMBRETE (migração sem perda de dados): leve JUNTOS o dump do banco, o\n";
echo "config/secrets.enc e a SECRET_MASTER_KEY. Sem os três, os dados cifrados\n";
echo "não podem ser recuperados.\n";
