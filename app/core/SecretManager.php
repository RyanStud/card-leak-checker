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
}