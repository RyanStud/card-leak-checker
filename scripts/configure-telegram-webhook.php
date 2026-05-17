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
$apiUrl = 'https://api.telegram.org/bot' . $botToken . '/setWebhook';

$payload = json_encode([
    'url' => $webhookUrl,
    'secret_token' => $secretToken,
    'allowed_updates' => ['message'],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

if ($payload === false) {
    exit("Falha ao gerar payload JSON\n");
}

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_TIMEOUT => 15,
]);

$response = curl_exec($ch);
$httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($response === false) {
    exit("Falha ao chamar setWebhook: {$curlError}\n");
}

echo "HTTP {$httpCode}\n" . $response . PHP_EOL;
