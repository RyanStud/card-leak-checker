<?php

/**
 * Criptografia de registros do banco (S.3.2).
 *
 * - S.3.2.a: gera uma chave simétrica AES-256 (DEK).
 * - S.3.2.b: a DEK é guardada DENTRO da gestão de segredos do projeto, como o
 *   segredo `DB_ENC_KEY` no cofre `config/secrets.enc` (cifrado com
 *   SHA-256(SECRET_MASTER_KEY)). Em runtime é lida via Config::secret('DB_ENC_KEY'),
 *   igual a DB_USER/CARD_VAULT_KEY. A master key continua sendo a única raiz de
 *   confiança (envelope encryption: master key -> DB_ENC_KEY -> dados).
 * - S.3.2.c/d: encrypt()/decrypt() cifram e decifram os campos sensíveis.
 *
 * Formato do segredo DB_ENC_KEY: "base64:<32 bytes>" (chave usada direto) ou
 * qualquer string (derivada com SHA-256). Sem perda de dados: valores cifrados
 * têm o prefixo "enc:v1:"; decrypt() devolve o valor intacto se não tiver o
 * prefixo (legado em claro) ou se a decifragem falhar.
 */
class DbCipher
{
    private const PREFIX = 'enc:v1:';
    private const CIPHER = 'aes-256-gcm';

    /** Arquivo da DEK do esquema antigo (pré-cofre), importado se existir. */
    private const LEGACY_KEY_FILE = '/storage/keys/db_master.key.enc';

    private static ?string $key = null;

    /**
     * Retorna a DEK (32 bytes) lendo o segredo DB_ENC_KEY do cofre.
     */
    public static function key(): string
    {
        if (self::$key !== null) {
            return self::$key;
        }

        $material = self::readVaultMaterial();
        if (trim($material) === '') {
            throw new RuntimeException(
                'DB_ENC_KEY ausente no cofre de segredos. Rode "composer setup" (ou php generate-secrets.php) para gerá-la.'
            );
        }

        self::$key = self::deriveKey($material);
        return self::$key;
    }

    /** Injeta o material da chave (usado após gerar/garantir no cofre em runtime). */
    public static function setKeyMaterial(string $material): void
    {
        self::$key = self::deriveKey($material);
    }

    /**
     * Garante que o segredo DB_ENC_KEY exista no cofre (gera+armazena se faltar,
     * importando a chave legada de storage/keys se houver) e o injeta.
     * Requer que o cofre já exista (config/secrets.enc).
     */
    public static function ensureVaultKeyMaterial(string $masterKey, string $secretsEncPath, string $projectRoot): string
    {
        if (!is_file($secretsEncPath)) {
            throw new RuntimeException('Cofre de segredos não encontrado: ' . $secretsEncPath);
        }

        $sm = new SecretManager($secretsEncPath, $masterKey);
        $secrets = $sm->all();
        $existing = isset($secrets['DB_ENC_KEY']) ? (string) $secrets['DB_ENC_KEY'] : '';

        $material = self::resolveKeyMaterial($existing !== '' ? $existing : null, $masterKey, $projectRoot);

        if ($existing !== $material) {
            $secrets['DB_ENC_KEY'] = $material;
            SecretManager::writeVault($secretsEncPath, $secrets, $masterKey);
            self::console('[S.3.2.b] Chave do banco armazenada na gestão de segredos (config/secrets.enc).');
        }

        self::setKeyMaterial($material);
        return $material;
    }

    /**
     * Decide o material de DB_ENC_KEY a usar, preservando o existente (sem perda
     * de dados). Se não houver, importa a chave legada de arquivo ou gera nova.
     */
    public static function resolveKeyMaterial(?string $existing, string $masterKey, string $projectRoot): string
    {
        if ($existing !== null && trim($existing) !== '') {
            return $existing; // carry-over -> não regenerar (evita perda de dados)
        }

        $legacy = $projectRoot . self::LEGACY_KEY_FILE;
        if (is_file($legacy)) {
            $dek = self::loadLegacyKeyFile($legacy, $masterKey);
            if ($dek !== null) {
                self::console('[S.3.2] DEK legada (storage/keys/db_master.key.enc) importada para o cofre de segredos.');
                return 'base64:' . base64_encode($dek);
            }
        }

        $dek = random_bytes(32);
        self::console('[S.3.2.a] Chave simétrica do banco (AES-256) gerada: base64:' . base64_encode($dek));
        return 'base64:' . base64_encode($dek);
    }

    public static function isEncrypted(?string $value): bool
    {
        return is_string($value) && str_starts_with($value, self::PREFIX);
    }

    /**
     * Cifra um valor. Idempotente; mantém null/'' intactos.
     */
    public static function encrypt(?string $plaintext, bool $deterministic = false): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return $plaintext;
        }
        if (self::isEncrypted($plaintext)) {
            return $plaintext;
        }

        $key = self::key();
        $iv = $deterministic ? self::deterministicIv($plaintext, $key) : random_bytes(12);
        $tag = '';

        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new RuntimeException('Falha ao cifrar valor para o banco: ' . openssl_error_string());
        }

        return self::PREFIX . base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decifra um valor. Devolve o original se não estiver cifrado (legado) ou se
     * a decifragem falhar — garantia de não perder informação.
     */
    public static function decrypt(?string $value): ?string
    {
        if (!self::isEncrypted($value)) {
            return $value;
        }

        $blob = base64_decode(substr((string) $value, strlen(self::PREFIX)), true);
        if ($blob === false || strlen($blob) <= 28) {
            return $value;
        }

        $iv = substr($blob, 0, 12);
        $tag = substr($blob, 12, 16);
        $ciphertext = substr($blob, 28);

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, self::key(), OPENSSL_RAW_DATA, $iv, $tag);

        return $plaintext === false ? $value : $plaintext;
    }

    /**
     * Console do back no formato usuario:hostname>mensagem (S.3.2.b/d).
     */
    public static function console(string $message): void
    {
        $identity = function_exists('hybrid_crypto_server_identity')
            ? hybrid_crypto_server_identity()
            : ((string) (getenv('USER') ?: getenv('USERNAME') ?: get_current_user() ?: 'web')
                . ':' . (string) (gethostname() ?: 'localhost'));

        $line = $identity . '>' . $message;
        error_log($line);

        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (is_dir($logDir) || @mkdir($logDir, 0755, true) || is_dir($logDir)) {
            @file_put_contents(
                $logDir . '/db-crypto.log',
                '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        }
    }

    // -----------------------------------------------------------------

    private static function readVaultMaterial(): string
    {
        if (function_exists('secret')) {
            $material = (string) secret('DB_ENC_KEY', '');
            if ($material !== '') {
                return $material;
            }
        }

        if (class_exists('Config')) {
            return (string) Config::secret('DB_ENC_KEY', '');
        }

        return '';
    }

    private static function deriveKey(string $material): string
    {
        if (str_starts_with($material, 'base64:')) {
            $decoded = base64_decode(substr($material, 7), true);
            if ($decoded !== false && strlen($decoded) === 32) {
                return $decoded;
            }
        }

        return hash('sha256', $material, true);
    }

    private static function deterministicIv(string $plaintext, string $key): string
    {
        $ivKey = hash_hmac('sha256', 'db-deterministic-iv', $key, true);
        return substr(hash_hmac('sha256', $plaintext, $ivKey, true), 0, 12);
    }

    private static function loadLegacyKeyFile(string $path, string $masterKey): ?string
    {
        $raw = file_get_contents($path);
        if ($raw === false || trim($raw) === '') {
            return null;
        }

        $payload = json_decode((string) base64_decode($raw, true), true);
        if (
            !is_array($payload)
            || !isset($payload['cipher'], $payload['iv'], $payload['tag'], $payload['value'])
        ) {
            return null;
        }

        $iv = base64_decode((string) $payload['iv'], true);
        $tag = base64_decode((string) $payload['tag'], true);
        $ciphertext = base64_decode((string) $payload['value'], true);
        if ($iv === false || $tag === false || $ciphertext === false) {
            return null;
        }

        $dek = openssl_decrypt(
            $ciphertext,
            (string) $payload['cipher'],
            hash('sha256', $masterKey, true),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        return ($dek === false || strlen($dek) !== 32) ? null : $dek;
    }
}
