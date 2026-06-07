<?php

/**
 * Criptografia híbrida (S.3.1).
 *
 * Fluxo:
 *   1. O back mantém um par de chaves RSA-2048 + um certificado X.509 auto-assinado.
 *   2. O front busca a chave pública (CryptoController::publicKey).
 *   3. O front gera uma chave de sessão simétrica (AES-256-GCM), cifra os dados do
 *      formulário com ela e cifra a própria chave de sessão com a chave pública RSA
 *      (RSA-OAEP).
 *   4. O back decifra a chave de sessão com a chave privada e, com ela, decifra os
 *      dados do formulário.
 *
 * Interoperabilidade Web Crypto (browser) <-> OpenSSL (PHP):
 *   - RSA-OAEP usa SHA-1 dos dois lados. O `openssl_private_decrypt` com
 *     OPENSSL_PKCS1_OAEP_PADDING usa SHA-1 por padrão; portanto o front também
 *     importa a chave pública como { name: 'RSA-OAEP', hash: 'SHA-1' }.
 *   - AES-256-GCM: o Web Crypto concatena o authentication tag (16 bytes) ao final
 *     do ciphertext. Aqui separamos os últimos 16 bytes para passar como tag ao
 *     `openssl_decrypt`.
 */

function hybrid_crypto_key_dir(): string
{
    return dirname(__DIR__, 2) . '/storage/keys';
}

/**
 * Garante que o par de chaves (e o certificado) exista em disco, gerando-o na
 * primeira execução. Retorna os caminhos e os PEMs carregados.
 *
 * @return array{private_pem:string,public_pem:string,certificate_pem:?string}
 */
function hybrid_crypto_ensure_keys(): array
{
    $dir = hybrid_crypto_key_dir();

    if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
        throw new RuntimeException('Não foi possível criar o diretório de chaves: ' . $dir);
    }

    $privPath = $dir . '/private.pem';
    $pubPath = $dir . '/public.pem';
    $certPath = $dir . '/certificate.pem';

    if (is_file($privPath) && is_file($pubPath)) {
        return [
            'private_pem' => (string) file_get_contents($privPath),
            'public_pem' => (string) file_get_contents($pubPath),
            'certificate_pem' => is_file($certPath) ? (string) file_get_contents($certPath) : null,
        ];
    }

    $config = [
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
        'digest_alg' => 'sha256',
    ];

    $res = openssl_pkey_new($config);
    if ($res === false) {
        throw new RuntimeException('Falha ao gerar o par de chaves RSA: ' . openssl_error_string());
    }

    $privPem = '';
    if (!openssl_pkey_export($res, $privPem, null, $config)) {
        throw new RuntimeException('Falha ao exportar a chave privada: ' . openssl_error_string());
    }

    $details = openssl_pkey_get_details($res);
    if ($details === false || empty($details['key'])) {
        throw new RuntimeException('Falha ao obter a chave pública: ' . openssl_error_string());
    }
    $pubPem = (string) $details['key'];

    file_put_contents($privPath, $privPem);
    @chmod($privPath, 0600);
    file_put_contents($pubPath, $pubPem);
    @chmod($pubPath, 0644);

    // Certificado X.509 auto-assinado (best-effort: se o ambiente OpenSSL não
    // estiver configurado para assinar CSR, seguimos apenas com o par de chaves).
    $certPem = null;
    try {
        $dn = [
            'countryName' => 'BR',
            'stateOrProvinceName' => 'Parana',
            'localityName' => 'Curitiba',
            'organizationName' => 'PUCPR - Ciberseguranca',
            'organizationalUnitName' => 'Card Leak Checker',
            'commonName' => (string) (gethostname() ?: 'card-leak-checker'),
        ];

        $csr = openssl_csr_new($dn, $res, $config);
        if ($csr !== false) {
            $cert = openssl_csr_sign($csr, null, $res, 365, $config);
            if ($cert !== false && openssl_x509_export($cert, $certPem)) {
                file_put_contents($certPath, $certPem);
                @chmod($certPath, 0644);
            } else {
                $certPem = null;
            }
        }
    } catch (\Throwable $e) {
        $certPem = null;
    }

    return [
        'private_pem' => $privPem,
        'public_pem' => $pubPem,
        'certificate_pem' => $certPem,
    ];
}

/**
 * Caminhos opcionais (via .env) para usar o certificado/chave reais do servidor
 * (ex.: Let's Encrypt) em vez do par próprio do app.
 *
 * @return array{cert_path:string,key_path:string}
 */
function hybrid_crypto_config(): array
{
    return [
        'cert_path' => function_exists('env') ? trim((string) env('HYBRID_CERT_PATH', '')) : '',
        'key_path' => function_exists('env') ? trim((string) env('HYBRID_PRIVATE_KEY_PATH', '')) : '',
    ];
}

/**
 * Material criptográfico ativo. Se HYBRID_CERT_PATH/HYBRID_PRIVATE_KEY_PATH
 * estiverem configurados, usa o certificado real do servidor; caso contrário,
 * usa o par auto-gerado em storage/keys.
 *
 * @return array{private_pem:string,public_pem:string,certificate_pem:?string,source:string,key_type:int}
 */
function hybrid_crypto_material(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cfg = hybrid_crypto_config();
    $certPath = $cfg['cert_path'];
    $keyPath = $cfg['key_path'];

    if ($certPath !== '' || $keyPath !== '') {
        if ($certPath === '' || $keyPath === '') {
            throw new RuntimeException('Configure HYBRID_CERT_PATH e HYBRID_PRIVATE_KEY_PATH juntos.');
        }
        if (!is_readable($certPath)) {
            throw new RuntimeException("Certificado não legível pelo web server: {$certPath}");
        }
        if (!is_readable($keyPath)) {
            throw new RuntimeException("Chave privada não legível pelo web server: {$keyPath}");
        }

        $certPem = (string) file_get_contents($certPath);
        $privPem = (string) file_get_contents($keyPath);

        $pub = openssl_pkey_get_public($certPem);
        if ($pub === false) {
            throw new RuntimeException('Falha ao extrair a chave pública do certificado: ' . openssl_error_string());
        }
        $details = openssl_pkey_get_details($pub);

        $cache = [
            'private_pem' => $privPem,
            'public_pem' => (string) ($details['key'] ?? ''),
            'certificate_pem' => $certPem,
            'source' => 'tls:' . $certPath,
            'key_type' => (int) ($details['type'] ?? -1),
        ];

        return $cache;
    }

    $keys = hybrid_crypto_ensure_keys();
    $pub = openssl_pkey_get_public($keys['public_pem']);
    $details = $pub !== false ? openssl_pkey_get_details($pub) : [];

    $cache = [
        'private_pem' => $keys['private_pem'],
        'public_pem' => $keys['public_pem'],
        'certificate_pem' => $keys['certificate_pem'],
        'source' => 'app:storage/keys',
        'key_type' => (int) ($details['type'] ?? OPENSSL_KEYTYPE_RSA),
    ];

    return $cache;
}

function hybrid_crypto_public_key_pem(): string
{
    return hybrid_crypto_material()['public_pem'];
}

function hybrid_crypto_certificate_pem(): ?string
{
    return hybrid_crypto_material()['certificate_pem'];
}

/**
 * Impressão digital SHA-256 (hex, em pares) da chave pública DER, útil para
 * conferência visual no certificado/console.
 */
function hybrid_crypto_public_key_fingerprint(): string
{
    $pem = hybrid_crypto_public_key_pem();
    $der = hybrid_crypto_pem_to_der($pem);
    $hash = strtoupper(hash('sha256', $der));

    return trim(chunk_split($hash, 2, ':'), ':');
}

function hybrid_crypto_pem_to_der(string $pem): string
{
    $body = preg_replace('/-----(BEGIN|END)[^-]+-----/', '', $pem);
    $body = preg_replace('/\s+/', '', (string) $body);

    return (string) base64_decode((string) $body, true);
}

/**
 * Decifra a chave de sessão (RSA-OAEP) e, com ela, o payload (AES-256-GCM).
 *
 * @param string $encKeyB64  Chave de sessão cifrada com a chave pública (base64).
 * @param string $ivB64      IV de 12 bytes do AES-GCM (base64).
 * @param string $payloadB64 Ciphertext||tag do AES-GCM (base64).
 *
 * @return array<string,mixed>|null Dados decifrados (JSON decodificado) ou null em falha.
 */
function hybrid_crypto_decrypt(string $encKeyB64, string $ivB64, string $payloadB64): ?array
{
    $material = hybrid_crypto_material();

    // RSA-OAEP exige chave RSA. Certificados ECDSA (comuns no Let's Encrypt) não
    // servem para este esquema — avisa de forma clara em vez de falhar silencioso.
    if ($material['key_type'] !== OPENSSL_KEYTYPE_RSA) {
        hybrid_crypto_console_log(
            'ERRO: o certificado configurado NÃO é RSA (key_type=' . $material['key_type']
            . '). RSA-OAEP requer uma chave RSA. Reemita o certificado com --key-type rsa'
            . ' ou use o par próprio do app (storage/keys).'
        );
        return null;
    }

    $encKey = base64_decode($encKeyB64, true);
    $iv = base64_decode($ivB64, true);
    $payload = base64_decode($payloadB64, true);

    if ($encKey === false || $iv === false || $payload === false) {
        return null;
    }

    if (strlen($iv) !== 12 || strlen($payload) <= 16) {
        return null;
    }

    $privateKey = openssl_pkey_get_private($material['private_pem']);
    if ($privateKey === false) {
        return null;
    }

    // 1) Decifrar a chave de sessão simétrica com a chave privada (RSA-OAEP/SHA-1).
    $sessionKey = '';
    $ok = openssl_private_decrypt($encKey, $sessionKey, $privateKey, OPENSSL_PKCS1_OAEP_PADDING);
    if (!$ok || strlen($sessionKey) !== 32) {
        return null;
    }

    // 2) Separar o authentication tag (últimos 16 bytes) do ciphertext (Web Crypto).
    $tag = substr($payload, -16);
    $ciphertext = substr($payload, 0, -16);

    // 3) Decifrar os dados com a chave de sessão (AES-256-GCM).
    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $sessionKey, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plaintext === false) {
        return null;
    }

    $data = json_decode($plaintext, true);

    return is_array($data) ? $data : null;
}

/**
 * Identidade do back no formato "usuario:hostname" (usuário do processo PHP).
 */
function hybrid_crypto_server_identity(): string
{
    $user = '';

    if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
        $info = @posix_getpwuid(posix_geteuid());
        if (is_array($info) && !empty($info['name'])) {
            $user = (string) $info['name'];
        }
    }

    if ($user === '') {
        $user = (string) (getenv('USER') ?: getenv('USERNAME') ?: get_current_user());
    }

    if ($user === '') {
        $user = 'web';
    }

    $host = (string) (gethostname() ?: php_uname('n'));
    if ($host === '') {
        $host = 'localhost';
    }

    return $user . ':' . $host;
}

/**
 * Imprime no console do back uma mensagem no formato "usuario:hostname>mensagem"
 * (S.3.1.f). Escreve no log de erros do PHP (stderr/Apache) e em um arquivo
 * dedicado que pode ser acompanhado com `tail -f`.
 */
function hybrid_crypto_console_log(string $message): void
{
    $line = hybrid_crypto_server_identity() . '>' . $message;

    // 1) Log de erros do PHP (aparece no console/stderr ou no error_log do Apache).
    error_log($line);

    // 2) Arquivo dedicado para acompanhamento via `tail -f`.
    $logDir = dirname(__DIR__, 2) . '/storage/logs';
    if (is_dir($logDir) || mkdir($logDir, 0755, true) || is_dir($logDir)) {
        $stamp = date('Y-m-d H:i:s');
        @file_put_contents(
            $logDir . '/hybrid-decrypt.log',
            "[$stamp] $line" . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
