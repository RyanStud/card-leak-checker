<?php

function cookie_consent_get(): ?array
{
    $raw = (string)($_COOKIE['lgpd_cookie_consent'] ?? '');
    if ($raw === '') {
        return null;
    }

    $decoded = base64_decode($raw, true);
    if ($decoded === false) {
        return null;
    }

    $data = json_decode($decoded, true);
    if (!is_array($data)) {
        return null;
    }

    return $data;
}

function cookie_consent_exists(): bool
{
    return cookie_consent_get() !== null;
}

function cookie_consent_set(bool $analytics): void
{
    $payload = [
        'essential' => true,
        'analytics' => $analytics,
        'saved_at' => date('c'),
    ];

    $value = base64_encode(json_encode($payload, JSON_UNESCAPED_UNICODE));

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    setcookie('lgpd_cookie_consent', $value, [
        'expires' => time() + (365 * 24 * 3600),
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    $_COOKIE['lgpd_cookie_consent'] = $value;
}
