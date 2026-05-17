<?php

/**
 * Polling do bot do Telegram.
 *
 * Roda em loop chamando getUpdates (long polling) e processa cada
 * update com a mesma lógica do webhook (TelegramController::processUpdate).
 *
 * Use quando o ambiente não pode receber webhooks externos (ex.: lab atrás
 * de VPN, dev local sem túnel público).
 *
 * Requisitos:
 * - TELEGRAM_MODE=polling no .env
 * - APP_KEY_TELEGRAM no config/secrets.enc
 * - SECRET_MASTER_KEY na env do shell (source ~/.bashrc se preciso)
 *
 * Como rodar em background:
 *   nohup php scripts/telegram-polling.php > storage/logs/polling.log 2>&1 &
 *
 * Para parar:
 *   ps aux | grep telegram-polling
 *   kill <PID>
 *
 * O offset é persistido em storage/telegram_offset.txt para evitar
 * reprocessar mensagens entre reinícios.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "scripts/telegram-polling.php deve ser executado via CLI.\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);

require_once $projectRoot . '/app/core/Env.php';
Env::load($projectRoot . '/.env');

require_once $projectRoot . '/app/core/SecretManager.php';
require_once $projectRoot . '/app/core/Config.php';
require_once $projectRoot . '/app/core/Database.php';
require_once $projectRoot . '/app/core/Controller.php';

require_once $projectRoot . '/app/helpers/env.php';
require_once $projectRoot . '/app/helpers/url.php';
require_once $projectRoot . '/app/helpers/security.php';
require_once $projectRoot . '/app/helpers/logger.php';
require_once $projectRoot . '/app/helpers/telegram.php';

require_once $projectRoot . '/app/models/TelegramAccount.php';
require_once $projectRoot . '/app/models/User.php';
require_once $projectRoot . '/app/controllers/TelegramController.php';

Config::init();

$mode = strtolower(trim((string) env('TELEGRAM_MODE', 'api')));
if ($mode !== 'polling') {
    fwrite(STDERR, "[erro] TELEGRAM_MODE != 'polling' (atual: {$mode}). Ajuste o .env e tente novamente.\n");
    exit(1);
}

$botToken = trim((string) secret('APP_KEY_TELEGRAM', ''));
if ($botToken === '') {
    fwrite(STDERR, "[erro] APP_KEY_TELEGRAM ausente. Confirme se SECRET_MASTER_KEY está no shell (source ~/.bashrc).\n");
    exit(1);
}

$offsetFile = $projectRoot . '/storage/telegram_offset.txt';
$pidFile = $projectRoot . '/storage/telegram-polling.pid';
$pollIntervalSec = (int) env('TELEGRAM_POLLING_INTERVAL', 10);
if ($pollIntervalSec < 1) {
    $pollIntervalSec = 10;
}

if (telegram_polling_pid_running($pidFile)) {
    fwrite(STDERR, "[erro] já existe uma instância em execução (pid em {$pidFile}).\n");
    exit(1);
}

@mkdir(dirname($pidFile), 0755, true);
file_put_contents($pidFile, (string) getmypid());
register_shutdown_function(static function () use ($pidFile) {
    @unlink($pidFile);
});

if (!telegram_polling_delete_webhook($botToken)) {
    fwrite(STDERR, "[aviso] Falha ao limpar webhook anterior. Continuando.\n");
}

$offset = telegram_polling_read_offset($offsetFile);
$controller = new TelegramController();

echo '[' . date('Y-m-d H:i:s') . "] polling iniciado. interval={$pollIntervalSec}s offset={$offset}\n";

$running = true;
if (function_exists('pcntl_async_signals')) {
    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, static function () use (&$running) {
        $running = false;
    });
    pcntl_signal(SIGINT, static function () use (&$running) {
        $running = false;
    });
}

while ($running) {
    try {
        $updates = telegram_polling_fetch($botToken, $offset, $pollIntervalSec);

        foreach ($updates as $update) {
            $updateId = (int) ($update['update_id'] ?? 0);
            if ($updateId <= 0) {
                continue;
            }

            try {
                $controller->processUpdate($update);
            } catch (Throwable $e) {
                app_log('polling_process_error update_id=' . $updateId . ' err=' . $e->getMessage());
            }

            $offset = $updateId + 1;
            telegram_polling_write_offset($offsetFile, $offset);
        }
    } catch (Throwable $e) {
        app_log('polling_loop_error ' . $e->getMessage());
        sleep(5);
    }
}

echo '[' . date('Y-m-d H:i:s') . "] polling encerrado.\n";

// ---------------------------------------------------------------------------

function telegram_polling_read_offset(string $file): int
{
    if (!file_exists($file)) {
        return 0;
    }

    $content = trim((string) file_get_contents($file));
    return (int) $content;
}

function telegram_polling_write_offset(string $file, int $offset): void
{
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    file_put_contents($file, (string) $offset, LOCK_EX);
}

function telegram_polling_fetch(string $botToken, int $offset, int $timeoutSec): array
{
    $url = 'https://api.telegram.org/bot' . $botToken . '/getUpdates';
    $payload = json_encode([
        'offset' => $offset,
        'timeout' => $timeoutSec,
        'allowed_updates' => ['message'],
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => $timeoutSec + 10,
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException('fetch_updates curl: ' . $error);
    }

    $data = json_decode($response, true);
    if (!is_array($data) || empty($data['ok'])) {
        throw new RuntimeException('fetch_updates resposta inválida: ' . $response);
    }

    return is_array($data['result'] ?? null) ? $data['result'] : [];
}

function telegram_polling_pid_running(string $pidFile): bool
{
    if (!file_exists($pidFile)) {
        return false;
    }

    $pid = (int) trim((string) file_get_contents($pidFile));
    if ($pid <= 0) {
        return false;
    }

    // posix_kill com signal 0 testa se o processo existe (POSIX). Em Windows
    // não rodamos polling, então este path não é exercido.
    if (function_exists('posix_kill')) {
        return @posix_kill($pid, 0);
    }

    // Fallback: tenta /proc/<pid> (Linux)
    return is_dir('/proc/' . $pid);
}

function telegram_polling_delete_webhook(string $botToken): bool
{
    $url = 'https://api.telegram.org/bot' . $botToken . '/deleteWebhook';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
    ]);
    $response = curl_exec($ch);
    $ok = $response !== false;
    curl_close($ch);

    return $ok;
}
