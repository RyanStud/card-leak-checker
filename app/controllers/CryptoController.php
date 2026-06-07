<?php

class CryptoController extends Controller
{
    /**
     * S.3.1.a - Expõe a chave pública RSA do back para o front montar a
     * criptografia híbrida. Também devolve o certificado e a impressão digital
     * para conferência.
     */
    public function publicKey(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        try {
            $keys = hybrid_crypto_ensure_keys();

            echo json_encode([
                'algorithm' => 'RSA-OAEP',
                'hash' => 'SHA-1',
                'keySize' => 2048,
                'publicKey' => $keys['public_pem'],
                'certificate' => $keys['certificate_pem'],
                'fingerprint' => hybrid_crypto_public_key_fingerprint(),
            ], JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Não foi possível obter a chave pública.']);
        }

        exit;
    }

    /**
     * S.3.1.a - Mostra o certificado X.509 (PEM) em texto puro, prático para
     * tirar o screenshot do certificado.
     */
    public function certificate(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');

        try {
            $keys = hybrid_crypto_ensure_keys();
            $cert = $keys['certificate_pem'];

            echo "# Fingerprint (SHA-256 da chave pública):\n";
            echo '# ' . hybrid_crypto_public_key_fingerprint() . "\n\n";

            if ($cert !== null && $cert !== '') {
                echo $cert;

                $parsed = openssl_x509_parse($cert);
                if (is_array($parsed)) {
                    echo "\n# --- Detalhes do certificado ---\n";
                    echo '# Subject: ' . json_encode($parsed['subject'] ?? [], JSON_UNESCAPED_SLASHES) . "\n";
                    echo '# Issuer:  ' . json_encode($parsed['issuer'] ?? [], JSON_UNESCAPED_SLASHES) . "\n";
                    if (isset($parsed['validFrom_time_t'], $parsed['validTo_time_t'])) {
                        echo '# Válido de ' . date('Y-m-d H:i:s', (int) $parsed['validFrom_time_t'])
                            . ' até ' . date('Y-m-d H:i:s', (int) $parsed['validTo_time_t']) . "\n";
                    }
                }
            } else {
                echo "# Certificado X.509 indisponível neste ambiente; exibindo a chave pública:\n\n";
                echo $keys['public_pem'];
            }
        } catch (\Throwable $e) {
            http_response_code(500);
            echo 'Não foi possível obter o certificado.';
        }

        exit;
    }
}
