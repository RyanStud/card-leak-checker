<?php

function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function clean_text(?string $value): string
{
    $value = trim((string)$value);
    $value = strip_tags($value);
    $result = preg_replace('/\s+/', ' ', $value);

    return $result !== null ? $result : '';
}

function clean_email(?string $value): string
{
    return trim((string)$value);
}

function clean_numeric_text(?string $value): string
{
    $result = preg_replace('/\D/', '', (string)$value);

    return $result !== null ? $result : '';
}