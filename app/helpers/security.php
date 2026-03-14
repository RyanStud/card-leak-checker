<?php

function generate_slug(string $text): string
{
    $text = trim($text);
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text);
    $text = trim($text, '-');

    return $text ?: 'projeto';
}

function card_digits_only(string $cardNumber): string
{
    return preg_replace('/\D/', '', $cardNumber);
}

function mask_bin(string $digits): string
{
    $first6 = substr($digits, 0, 6);
    return str_pad($first6, 6, '*') . '******';
}

function mask_last4(string $digits): string
{
    return substr($digits, -4);
}

function card_fingerprint(string $digits): string
{
    return hash('sha256', $digits);
}

function looks_like_valid_card(string $digits): bool
{
    return preg_match('/^\d{13,19}$/', $digits) === 1;
}

function demo_card_leak_check(string $digits): string
{
    $last4 = substr($digits, -4);

    $testLeakedLast4 = ['1111', '1234', '9999', '0000'];

    if (in_array($last4, $testLeakedLast4, true)) {
        return 'possible_leak_found';
    }

    return 'no_evidence_found';
}