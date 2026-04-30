<?php

function captcha_get_or_create(string $context): array
{
    $key = 'captcha_' . $context;
    $current = $_SESSION[$key] ?? null;

    if (is_array($current) && isset($current['question'], $current['answer_hash'], $current['expires_at'])) {
        if ((int)$current['expires_at'] >= time()) {
            return $current;
        }
    }

    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $answer = (string)($a + $b);

    $captcha = [
        'question' => 'Quanto e ' . $a . ' + ' . $b . '?',
        'answer_hash' => hash('sha256', $answer),
        'expires_at' => time() + 600,
    ];

    $_SESSION[$key] = $captcha;
    return $captcha;
}

function captcha_validate(string $context, string $input): bool
{
    $key = 'captcha_' . $context;
    $current = $_SESSION[$key] ?? null;

    if (!is_array($current)) {
        return false;
    }

    if ((int)($current['expires_at'] ?? 0) < time()) {
        unset($_SESSION[$key]);
        return false;
    }

    $normalized = clean_numeric_text($input);
    $ok = hash_equals((string)$current['answer_hash'], hash('sha256', (string)$normalized));

    unset($_SESSION[$key]);

    return $ok;
}

function captcha_reset(string $context): void
{
    unset($_SESSION['captcha_' . $context]);
}
