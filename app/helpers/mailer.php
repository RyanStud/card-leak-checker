<?php

function send_demo_mail(string $to, string $subject, string $body): bool
{
    $mode = env('MAIL_MODE', 'log');

    if ($mode === 'log') {
        app_log("MAIL TO: {$to} | SUBJECT: {$subject} | BODY: {$body}");
        return true;
    }

    if ($mode === 'mailtrap_api') {
        return send_mailtrap_api_mail($to, $subject, $body);
    }

    app_log("MAIL MODE inválido: {$mode}");
    return false;
}

function send_mailtrap_api_mail(string $to, string $subject, string $body): bool
{
    $token = (string) required_secret('MAILTRAP_API_TOKEN');
    $fromEmail = (string) env('MAIL_FROM', 'no-reply@example.com');
    $fromName = (string) env('MAIL_FROM_NAME', 'Card Leak Checker');

    if ($token === '') {
        app_log('MAILTRAP_API_TOKEN não configurado.');
        return false;
    }

    $payload = [
        'from' => [
            'email' => $fromEmail,
            'name' => $fromName,
        ],
        'to' => [
            [
                'email' => $to,
            ]
        ],
        'subject' => $subject,
        'text' => $body,
        'category' => 'Password Reset',
    ];

    $ch = curl_init('https://send.api.mailtrap.io/api/send');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
            'Api-Token: ' . $token,
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($curlError) {
        app_log("Erro cURL Mailtrap: {$curlError}");
        return false;
    }

    if ($httpCode >= 200 && $httpCode < 300) {
        app_log("MAILTRAP API OK | TO: {$to} | SUBJECT: {$subject} | RESPONSE: {$response}");
        return true;
    }

    app_log("MAILTRAP API ERRO | HTTP: {$httpCode} | RESPONSE: {$response}");
    return false;
}