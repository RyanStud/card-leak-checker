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

function luhn_is_valid(string $digits): bool
{
    $sum = 0;
    $alt = false;

    for ($i = strlen($digits) - 1; $i >= 0; $i--) {
        $n = (int)$digits[$i];

        if ($alt) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }

        $sum += $n;
        $alt = !$alt;
    }

    return $sum % 10 === 0;
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

function client_ip(): string
{
    $remoteAddr = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if (!filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
        return '0.0.0.0';
    }

    if (!is_trusted_proxy_ip($remoteAddr)) {
        return $remoteAddr;
    }

    $cfIp = trim((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
    if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
        return $cfIp;
    }

    $forwardedFor = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    if ($forwardedFor !== '') {
        $ips = array_map('trim', explode(',', $forwardedFor));

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return $remoteAddr;
}

function is_trusted_proxy_ip(string $ip): bool
{
    $trusted = trim((string)env('TRUSTED_PROXIES', ''));
    if ($trusted === '') {
        return false;
    }

    $entries = array_filter(array_map('trim', explode(',', $trusted)));

    foreach ($entries as $entry) {
        if ($entry === $ip) {
            return true;
        }

        if (str_contains($entry, '/') && ip_matches_cidr($ip, $entry)) {
            return true;
        }
    }

    return false;
}

function ip_matches_cidr(string $ip, string $cidr): bool
{
    [$subnet, $maskBits] = array_pad(explode('/', $cidr, 2), 2, null);

    if ($subnet === null || $maskBits === null || !is_numeric($maskBits)) {
        return false;
    }

    $mask = (int)$maskBits;
    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);

    if ($ipLong === false || $subnetLong === false || $mask < 0 || $mask > 32) {
        return false;
    }

    $maskLong = $mask === 0 ? 0 : (-1 << (32 - $mask));

    return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
}

function client_country(): string
{
    $headerKeys = [
        'HTTP_CF_IPCOUNTRY',
        'HTTP_X_COUNTRY_CODE',
        'HTTP_X_COUNTRY',
        'HTTP_GEOIP_COUNTRY_CODE',
        'HTTP_GEOIP_COUNTRY_NAME',
        'GEOIP_COUNTRY_CODE',
        'GEOIP_COUNTRY_NAME',
    ];

    foreach ($headerKeys as $key) {
        if (!empty($_SERVER[$key])) {
            $country = trim((string)$_SERVER[$key]);
            if ($country !== '') {
                return $country;
            }
        }
    }

    $ip = client_ip();
    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        return 'Unknown';
    }

    if (
        $ip === '127.0.0.1'
        || $ip === '::1'
        || str_starts_with($ip, '10.')
        || str_starts_with($ip, '192.168.')
        || preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ip) === 1
    ) {
        return 'Local';
    }

    $country = lookup_country_by_ip($ip);
    return $country ?: 'Unknown';
}

function lookup_country_by_ip(string $ip): ?string
{
    $url = 'https://ipwho.is/' . rawurlencode($ip);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 1,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    if (!is_array($data) || !($data['success'] ?? false)) {
        return null;
    }

    $country = trim((string)($data['country'] ?? ''));
    return $country !== '' ? $country : null;
}

function is_suspicious_path(string $uri): bool
{
    $patterns = [
        '/\.env/i',
        '/wp-admin/i',
        '/wp-login/i',
        '/phpmyadmin/i',
        '/vendor/i',
        '/composer\.json/i',
        '/composer\.lock/i',
        '/\.git/i',
        '/adminer/i',
        '/boaform/i',
        '/shell/i',
        '/config\.php/i',
        '/setup\.php/i',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $uri) === 1) {
            return true;
        }
    }

    return false;
}
