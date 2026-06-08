<?php

class SecretManager
{
    private array $secrets = [];

    public function __construct(string $filePath, string $masterKey)
    {
        if ($masterKey === '') {
            throw new RuntimeException('SECRET_MASTER_KEY não informado.');
        }

        if (!file_exists($filePath)) {
            throw new RuntimeException('Arquivo de segredos não encontrado: ' . $filePath);
        }

        $raw = file_get_contents($filePath);

        if ($raw === false || trim($raw) === '') {
            throw new RuntimeException('Arquivo de segredos vazio ou ilegível.');
        }

        $decodedEnvelope = base64_decode($raw, true);

        if ($decodedEnvelope === false) {
            throw new RuntimeException('Envelope de segredos inválido.');
        }

        $payload = json_decode($decodedEnvelope, true);

        if (
            !is_array($payload) ||
            !isset($payload['cipher']) ||
            !isset($payload['iv']) ||
            !isset($payload['tag']) ||
            !isset($payload['value'])
        ) {
            throw new RuntimeException('Estrutura do payload de segredos inválida.');
        }

        $cipher = (string) $payload['cipher'];
        $iv = base64_decode((string) $payload['iv'], true);
        $tag = base64_decode((string) $payload['tag'], true);
        $ciphertext = base64_decode((string) $payload['value'], true);

        if ($iv === false || $tag === false || $ciphertext === false) {
            throw new RuntimeException('Falha ao decodificar o conteúdo criptografado.');
        }

        $derivedKey = hash('sha256', $masterKey, true);

        $plaintext = openssl_decrypt(
            $ciphertext,
            $cipher,
            $derivedKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Falha ao descriptografar os segredos.');
        }

        $decodedSecrets = json_decode($plaintext, true);

        if (!is_array($decodedSecrets)) {
            throw new RuntimeException('Conteúdo descriptografado inválido.');
        }

        $this->secrets = $decodedSecrets;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->secrets[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->secrets);
    }

    public function all(): array
    {
        return $this->secrets;
    }

    /**
     * Recriptografa e grava o cofre completo (mesmo envelope do generate-secrets).
     * Usado para acrescentar segredos em runtime (ex.: DB_ENC_KEY do S.3.2) sem
     * precisar do config/secrets.json em claro.
     *
     * @param array<string,mixed> $secrets
     */
    public static function writeVault(string $filePath, array $secrets, string $masterKey): void
    {
        if ($masterKey === '') {
            throw new RuntimeException('SECRET_MASTER_KEY não informado.');
        }

        $cipher = 'aes-256-gcm';
        $iv = random_bytes(12);
        $tag = '';

        $plaintext = json_encode($secrets, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($plaintext === false) {
            throw new RuntimeException('Falha ao serializar os segredos.');
        }

        $encrypted = openssl_encrypt(
            $plaintext,
            $cipher,
            hash('sha256', $masterKey, true),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false) {
            throw new RuntimeException('Falha ao criptografar os segredos.');
        }

        $payload = base64_encode((string) json_encode([
            'cipher' => $cipher,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'value' => base64_encode($encrypted),
        ], JSON_UNESCAPED_SLASHES));

        if (file_put_contents($filePath, $payload) === false) {
            throw new RuntimeException('Falha ao gravar o cofre de segredos em ' . $filePath);
        }
    }
}