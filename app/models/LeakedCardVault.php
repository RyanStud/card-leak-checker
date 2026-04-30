<?php

class LeakedCardVault
{
    private const CIPHER = 'aes-256-gcm';

    private PDO $pdo;
    private string $lookupPepper;
    private string $encryptionKey;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->lookupPepper = $this->resolveLookupPepper();
        $this->encryptionKey = $this->resolveEncryptionKey();
    }

    public function isLeakedCard(string $digits, string $expiryMonth, string $expiryYear, string $cvv): bool
    {
        $lookupHash = $this->lookupHash($digits);

        $stmt = $this->pdo->prepare(
            'SELECT payload_ciphertext, payload_iv, payload_tag
             FROM leaked_cards_vault
             WHERE card_lookup_hash = ?'
        );
        $stmt->execute([$lookupHash]);

        $rows = $stmt->fetchAll();

        foreach ($rows as $row) {
            $record = $this->decryptPayload(
                (string)$row['payload_ciphertext'],
                (string)$row['payload_iv'],
                (string)$row['payload_tag']
            );

            if ($record === null) {
                continue;
            }

            if (
                hash_equals((string)$record['card_number'], $digits)
                && hash_equals((string)$record['expiry_month'], $expiryMonth)
                && hash_equals((string)$record['expiry_year'], $expiryYear)
                && hash_equals((string)$record['cvv'], $cvv)
            ) {
                return true;
            }
        }

        return false;
    }

    public function importFromCsv(string $csvPath, string $sourceBatch = 'sample-local'): int
    {
        if (!is_file($csvPath) || !is_readable($csvPath)) {
            throw new RuntimeException('CSV nÃ£o encontrado ou sem permissÃ£o de leitura: ' . $csvPath);
        }

        $handle = fopen($csvPath, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Falha ao abrir CSV: ' . $csvPath);
        }

        $this->pdo->beginTransaction();

        try {
            $header = fgetcsv($handle);
            if (!is_array($header)) {
                throw new RuntimeException('CSV vazio.');
            }

            $insertStmt = $this->pdo->prepare(
                'INSERT INTO leaked_cards_vault
                (card_lookup_hash, payload_ciphertext, payload_iv, payload_tag, source_batch)
                VALUES (?, ?, ?, ?, ?)'
            );

            $inserted = 0;

            while (($row = fgetcsv($handle)) !== false) {
                if (!is_array($row) || count($row) < 4) {
                    continue;
                }

                $digits = preg_replace('/\D/', '', (string)$row[0]) ?? '';
                $month = preg_replace('/\D/', '', (string)$row[1]) ?? '';
                $year = preg_replace('/\D/', '', (string)$row[2]) ?? '';
                $cvv = preg_replace('/\D/', '', (string)$row[3]) ?? '';

                $month = str_pad(substr($month, 0, 2), 2, '0', STR_PAD_LEFT);
                $year = substr($year, 0, 4);

                if (
                    !looks_like_valid_card($digits)
                    || !luhn_is_valid($digits)
                    || preg_match('/^\d{2}$/', $month) !== 1
                    || preg_match('/^\d{4}$/', $year) !== 1
                    || preg_match('/^\d{3,4}$/', $cvv) !== 1
                ) {
                    continue;
                }

                $payload = $this->encryptPayload([
                    'card_number' => $digits,
                    'expiry_month' => $month,
                    'expiry_year' => $year,
                    'cvv' => $cvv,
                ]);

                $insertStmt->execute([
                    $this->lookupHash($digits),
                    $payload['ciphertext'],
                    $payload['iv'],
                    $payload['tag'],
                    $sourceBatch,
                ]);

                $inserted++;
            }

            $this->pdo->commit();
            fclose($handle);

            return $inserted;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            fclose($handle);
            throw $e;
        }
    }

    private function lookupHash(string $digits): string
    {
        return hash_hmac('sha256', $digits, $this->lookupPepper);
    }

    private function encryptPayload(array $payload): array
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new RuntimeException('Falha ao serializar payload do cartÃ£o.');
        }

        $iv = random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $json,
            self::CIPHER,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Falha ao criptografar payload do cartÃ£o.');
        }

        return [
            'ciphertext' => $ciphertext,
            'iv' => $iv,
            'tag' => $tag,
        ];
    }

    private function decryptPayload(string $ciphertext, string $iv, string $tag): ?array
    {
        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            return null;
        }

        $decoded = json_decode($plaintext, true);
        if (!is_array($decoded)) {
            return null;
        }

        return $decoded;
    }

    private function resolveLookupPepper(): string
    {
        $key = (string)required_secret('CARD_LOOKUP_PEPPER');

        if (trim($key) === '') {
            throw new RuntimeException('Segredo CARD_LOOKUP_PEPPER ausente.');
        }

        return $key;
    }

    private function resolveEncryptionKey(): string
    {
        $rawMaterial = (string)required_secret('CARD_VAULT_KEY');

        if (trim($rawMaterial) === '') {
            throw new RuntimeException('Segredo CARD_VAULT_KEY ausente.');
        }

        if (str_starts_with($rawMaterial, 'base64:')) {
            $decoded = base64_decode(substr($rawMaterial, 7), true);
            if ($decoded !== false && $decoded !== '') {
                return hash('sha256', $decoded, true);
            }
        }

        return hash('sha256', $rawMaterial, true);
    }
}
