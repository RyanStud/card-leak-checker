<?php

/**
 * DEPLOY DA CRIPTOGRAFIA (S.3.1 + S.3.2) — script único para rodar sem terminal.
 *
 * Pensado para hospedagem compartilhada (ex.: Hostinger): você sobe o projeto e
 * agenda este arquivo num Cron Job (modo PHP). Ele:
 *   - garante o par de chaves da cripto híbrida (S.3.1) conforme CERTIFICADO;
 *   - garante a DB_ENC_KEY DENTRO do cofre de segredos (config/secrets.enc) — NÃO
 *     precisa de secrets.json: ele abre o secrets.enc atual, adiciona a chave e
 *     regrava (S.3.2.a/b);
 *   - alarga as colunas e cifra os registros ainda em claro (S.3.2.c), sem perder
 *     dados (idempotente — pode rodar de novo à vontade).
 *
 * É um ONE-SHOT: depois de rodar uma vez com sucesso, apague o Cron Job.
 *
 * Cron (modo PHP): aponte para  scripts/deploy-encryption.php
 * Cron (Personalizado):  php /CAMINHO/public_html/scripts/deploy-encryption.php
 */

// Segurança: bloqueia execução via navegador (só CLI/cron).
if (PHP_SAPI !== 'cli' && (!empty($_SERVER['HTTP_HOST']) || !empty($_SERVER['REQUEST_METHOD']))) {
    http_response_code(403);
    exit('Forbidden');
}

$root = dirname(__DIR__);

require $root . '/app/core/Env.php';
Env::load($root . '/.env');
require $root . '/app/core/SecretManager.php';
require $root . '/app/core/Config.php';
require $root . '/app/helpers/env.php';
require $root . '/app/helpers/hybrid_crypto.php';
require $root . '/app/core/DbCipher.php';
require $root . '/app/core/Database.php';
require $root . '/app/helpers/db_migrate.php';

Config::init();

echo "=== Deploy da criptografia (S.3.1 + S.3.2) ===\n";

$master = Config::masterKey();
if ($master === '') {
    fwrite(STDERR, "[ERRO] SECRET_MASTER_KEY ausente. Coloque-a no .env do projeto e rode de novo.\n");
    exit(1);
}

// ---- S.3.1: prepara o par de chaves da cripto híbrida -------------------
echo "\n[S.3.1] Preparando o material da cripto híbrida...\n";
try {
    $mat = hybrid_crypto_material();
    echo "  fonte: {$mat['source']}\n";
} catch (\Throwable $e) {
    error_log('deploy-encryption (S.3.1): ' . $e->getMessage());
    echo "  [aviso] não foi possível preparar a chave híbrida (detalhe no log de erros).\n";
}

// ---- S.3.2: chave do banco no cofre + migração --------------------------
echo "\n[S.3.2] Criptografia do banco...\n";
try {
    $pdo = Database::getConnection();
} catch (\Throwable $e) {
    error_log('deploy-encryption (DB connect): ' . $e->getMessage());
    fwrite(STDERR, "[ERRO] Não conectou no banco (detalhe no log de erros).\n");
    exit(1);
}

try {
    $secretsEnc = $root . '/' . ltrim((string) Env::get('SECRETS_FILE', 'config/secrets.enc'), '/');
    DbCipher::ensureVaultKeyMaterial($master, $secretsEnc, $root);
    echo "  DB_ENC_KEY garantida no cofre (config/secrets.enc).\n";
} catch (\Throwable $e) {
    error_log('deploy-encryption (DB_ENC_KEY): ' . $e->getMessage());
    fwrite(STDERR, "[ERRO] Não foi possível preparar a DB_ENC_KEY no cofre (detalhe no log de erros).\n");
    exit(1);
}

try {
    db_widen_user_columns($pdo);
    $updated = db_backfill_user_encryption($pdo);
    echo $updated > 0
        ? "  Backfill: {$updated} usuário(s) cifrado(s).\n"
        : "  Banco já cifrado (nada a fazer).\n";
} catch (\Throwable $e) {
    error_log('deploy-encryption (migracao): ' . $e->getMessage());
    fwrite(STDERR, "[ERRO] Migração do banco falhou (detalhe no log de erros).\n");
    exit(1);
}

echo "\n=== Concluído. Pode APAGAR este Cron Job. ===\n";
echo "Backup/migração: leve JUNTOS o dump do banco + config/secrets.enc + SECRET_MASTER_KEY.\n";
