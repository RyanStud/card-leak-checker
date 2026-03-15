<?php

function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function clean_text(?string $value): string
{
    $value = trim((string)$value);
    $value = strip_tags($value);
    return preg_replace('/\s+/', ' ', $value) ?? '';
}

function clean_email(?string $value): string
{
    return trim((string)$value);
}

function clean_numeric_text(?string $value): string
{
    return preg_replace('/\D/', '', (string)$value) ?? '';
}