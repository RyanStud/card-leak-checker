<?php

require __DIR__ . '/app/core/Env.php';
Env::load(__DIR__ . '/.env');

$masterKey = getenv('SECRET_MASTER_KEY');

if (!is_string($masterKey) || trim($masterKey) === '') {
    $masterKeyFile = (string) Env::get('MASTER_KEY_FILE', '');

    if (trim($masterKeyFile) === '') {
        exit("Defina SECRET_MASTER_KEY ou MASTER_KEY_FILE no .env antes de rodar.\n");
    }

    $isAbsolutePath = preg_match('/^[A-Za-z]:\\\\/', $masterKeyFile) === 1 || str_starts_with($masterKeyFile, '/');
    $resolvedPath = $isAbsolutePath
        ? $masterKeyFile
        : __DIR__ . DIRECTORY_SEPARATOR . ltrim($masterKeyFile, '/\\');

    $realPath = realpath($resolvedPath);
    if ($realPath === false || !is_file($realPath)) {
        exit("Arquivo de master key não encontrado: {$masterKeyFile}\n");
    }

    $fileKey = file_get_contents($realPath);
    if ($fileKey === false || trim($fileKey) === '') {
        exit("Arquivo de master key vazio ou ilegível: {$masterKeyFile}\n");
    }

    $masterKey = trim($fileKey);
} else {
    $masterKey = trim($masterKey);
}

$inputFile = __DIR__ . '/config/secrets.json';
$outputFile = __DIR__ . '/config/secrets.enc';

if (!file_exists($inputFile)) {
    exit("Arquivo não encontrado: config/secrets.json\n");
}

$plaintext = file_get_contents($inputFile);
if ($plaintext === false || trim($plaintext) === '') {
    exit("config/secrets.json vazio ou ilegível.\n");
}

$decoded = json_decode($plaintext, true);
if (!is_array($decoded)) {
    exit("config/secrets.json não contém JSON válido.\n");
}

$cipher = 'aes-256-gcm';
$iv = random_bytes(12);
$tag = '';

$encrypted = openssl_encrypt(
    $plaintext,
    $cipher,
    hash('sha256', $masterKey, true),
    OPENSSL_RAW_DATA,
    $iv,
    $tag
);

if ($encrypted === false) {
    exit("Falha ao criptografar os segredos.\n");
}

$payload = [
    'cipher' => $cipher,
    'iv' => base64_encode($iv),
    'tag' => base64_encode($tag),
    'value' => base64_encode($encrypted),
];

$result = file_put_contents(
    $outputFile,
    base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE))
);

if ($result === false) {
    exit("Falha ao gravar config/secrets.enc\n");
}

echo "Arquivo config/secrets.enc gerado com sucesso.\n";
echo "Apague o config/secrets.json depois de validar.\n";