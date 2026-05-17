<?php

function telegram_send_message(int $chatId, string $text): bool
{
    $mode = strtolower(trim((string)env('TELEGRAM_MODE', 'api')));

    if ($mode === 'log') {
        app_log('telegram_log_mode chat_id=' . $chatId . ' text=' . $text);
        return true;
    }

    $botToken = trim((string)secret('APP_KEY_TELEGRAM', ''));
    if ($botToken === '') {
        app_log('telegram_send_skipped token_missing');
        return false;
    }

    $url = 'https://api.telegram.org/bot' . $botToken . '/sendMessage';
    $payload = json_encode([
        'chat_id' => $chatId,
        'text' => $text,
        'disable_web_page_preview' => true,
    ], JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        app_log('telegram_send_skipped payload_encode_error');
        return false;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 10,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        app_log('telegram_send_error chat_id=' . $chatId . ' curl=' . $curlError);
        return false;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        app_log('telegram_send_http_error chat_id=' . $chatId . ' http=' . $httpCode . ' body=' . $response);
        return false;
    }

    return true;
}
