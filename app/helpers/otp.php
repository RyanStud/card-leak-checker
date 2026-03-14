<?php

function base32_encode_custom(string $data): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binaryString = '';

    for ($i = 0; $i < strlen($data); $i++) {
        $binaryString .= str_pad(decbin(ord($data[$i])), 8, '0', STR_PAD_LEFT);
    }

    $chunks = str_split($binaryString, 5);
    $base32 = '';

    foreach ($chunks as $chunk) {
        if (strlen($chunk) < 5) {
            $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        }
        $base32 .= $alphabet[bindec($chunk)];
    }

    return $base32;
}

function base32_decode_custom(string $base32): string
{
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $base32 = strtoupper($base32);
    $binaryString = '';

    for ($i = 0; $i < strlen($base32); $i++) {
        $pos = strpos($alphabet, $base32[$i]);
        if ($pos !== false) {
            $binaryString .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }
    }

    $bytes = str_split($binaryString, 8);
    $result = '';

    foreach ($bytes as $byte) {
        if (strlen($byte) === 8) {
            $result .= chr(bindec($byte));
        }
    }

    return $result;
}

function generate_totp_secret(int $length = 20): string
{
    return base32_encode_custom(random_bytes($length));
}

function get_totp_code(string $secret, ?int $timestamp = null): string
{
    $timestamp ??= time();
    $counter = floor($timestamp / 30);

    $secretKey = base32_decode_custom($secret);
    $binaryCounter = pack('N*', 0) . pack('N*', $counter);

    $hash = hash_hmac('sha1', $binaryCounter, $secretKey, true);
    $offset = ord(substr($hash, -1)) & 0x0F;

    $truncatedHash = substr($hash, $offset, 4);
    $value = unpack('N', $truncatedHash)[1] & 0x7FFFFFFF;
    $modulo = $value % 1000000;

    return str_pad((string)$modulo, 6, '0', STR_PAD_LEFT);
}

function verify_totp_code(string $secret, string $code, int $window = 1): bool
{
    $code = preg_replace('/\D/', '', $code);
    $currentTime = time();

    for ($i = -$window; $i <= $window; $i++) {
        $testTime = $currentTime + ($i * 30);
        if (hash_equals(get_totp_code($secret, $testTime), $code)) {
            return true;
        }
    }

    return false;
}

function generate_otpauth_uri(string $email, string $secret, ?string $issuer = null): string
{
    $issuer = $issuer ?: env('TOTP_ISSUER', 'CardLeakChecker');

    $issuerEncoded = rawurlencode($issuer);
    $emailEncoded = rawurlencode($email);

    return "otpauth://totp/{$issuerEncoded}:{$emailEncoded}?secret={$secret}&issuer={$issuerEncoded}&algorithm=SHA1&digits=6&period=30";
}