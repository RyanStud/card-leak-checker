<?php

require_once __DIR__ . '/app/core/Env.php';
Env::load(__DIR__ . '/.env');

require_once __DIR__ . '/app/core/SecretManager.php';
require_once __DIR__ . '/app/helpers/hybrid_crypto.php';
require_once __DIR__ . '/app/core/DbCipher.php';

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

// S.3.2 - garante DB_ENC_KEY no cofre. Preserva o existente (NUNCA regenera, pra
// não perder dados); se faltar, carrega do secrets.enc atual ou gera nova.
$existingDek = isset($decoded['DB_ENC_KEY']) ? trim((string) $decoded['DB_ENC_KEY']) : '';
if ($existingDek === '' && file_exists($outputFile)) {
    try {
        $currentVault = new SecretManager($outputFile, $masterKey);
        $existingDek = trim((string) $currentVault->get('DB_ENC_KEY', ''));
    } catch (\Throwable $e) {
        // cofre atual ilegível com esta master key — segue para gerar nova.
    }
}

$dekMaterial = DbCipher::resolveKeyMaterial($existingDek !== '' ? $existingDek : null, $masterKey, __DIR__);

if (($decoded['DB_ENC_KEY'] ?? '') !== $dekMaterial) {
    $decoded['DB_ENC_KEY'] = $dekMaterial;
    // grava de volta no secrets.json (fonte) e recompõe o texto a ser cifrado
    file_put_contents(
        $inputFile,
        json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n"
    );
    $plaintext = (string) json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
DbCipher::console('[S.3.2.b] Chave do banco (DB_ENC_KEY) armazenada na gestão de segredos (config/secrets.enc).');
echo "Apague o config/secrets.json depois de validar.\n";