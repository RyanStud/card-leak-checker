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

    $url = 'https://api.telegram.org/bot' . rawurlencode($botToken) . '/sendMessage';
    $payload = json_encode([
        'chat_id' => $chatId,
        'text' => $text,
        'disable_web_page_preview' => true,
    ], JSON_UNESCAPED_UNICODE);

    if ($payload === false) {
        app_log('telegram_send_skipped payload_encode_error');
        return false;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 4,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        app_log('telegram_send_error chat_id=' . $chatId);
        return false;
    }

    return true;
}
