<?php

require __DIR__ . '/../app/core/Env.php';
Env::load(dirname(__DIR__) . '/.env');

require __DIR__ . '/../app/core/SecretManager.php';
require __DIR__ . '/../app/core/Config.php';
require __DIR__ . '/../app/helpers/env.php';
require __DIR__ . '/../app/helpers/url.php';
Config::init();

$botToken = trim((string)secret('APP_KEY_TELEGRAM', ''));
$secretToken = trim((string)secret('TELEGRAM_WEBHOOK_SECRET', ''));
$appUrl = rtrim((string)env('APP_URL', ''), '/');

if ($botToken === '') {
    exit("APP_KEY_TELEGRAM ausente em secrets.enc\n");
}

if ($appUrl === '') {
    exit("APP_URL ausente no .env\n");
}

if ($secretToken === '') {
    exit("TELEGRAM_WEBHOOK_SECRET ausente em secrets.enc\n");
}

$webhookUrl = $appUrl . '/webhook/telegram';
$apiUrl = 'https://api.telegram.org/bot' . rawurlencode($botToken) . '/setWebhook';

$payload = json_encode([
    'url' => $webhookUrl,
    'secret_token' => $secretToken,
    'allowed_updates' => ['message'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($payload === false) {
    exit("Falha ao gerar payload JSON\n");
}

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $payload,
        'timeout' => 10,
        'ignore_errors' => true,
    ],
]);

$response = file_get_contents($apiUrl, false, $context);
if ($response === false) {
    exit("Falha ao chamar setWebhook\n");
}

echo $response . PHP_EOL;
