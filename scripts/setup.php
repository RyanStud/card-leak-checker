<?php

/**
 * Setup interativo do Card Leak Checker.
 *
 * - Pergunta a SECRET_MASTER_KEY uma única vez (input oculto)
 * - Persiste a chave como variável de ambiente do SO
 *   - Windows: setx (escopo do usuário)
 *   - Linux/macOS: linha 'export' em ~/.bashrc ou ~/.zshrc
 * - Regenera config/secrets.enc a partir de config/secrets.json
 *
 * Idempotente: se a chave já estiver no ambiente, pula a entrada e só
 * regenera o secrets.enc quando há secrets.json para criptografar.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "scripts/setup.php deve ser executado via CLI.\n");
    exit(1);
}

$projectRoot = dirname(__DIR__);

require_once $projectRoot . '/app/core/Env.php';
Env::load($projectRoot . '/.env');

$isWindows = PHP_OS_FAMILY === 'Windows';
$secretsJson = $projectRoot . '/config/secrets.json';
$secretsEnc = $projectRoot . '/config/secrets.enc';

echo "=== Card Leak Checker :: Setup ===\n";

$masterKey = read_master_key_from_env();

if ($masterKey !== '') {
    echo "[ok] SECRET_MASTER_KEY já presente no ambiente do processo.\n";
} else {
    echo "Defina a SECRET_MASTER_KEY (chave mestra de criptografia).\n";
    echo "Ela será gravada como variável de ambiente do SO e usada apenas pela aplicação.\n";
    echo "A entrada não será exibida.\n\n";
    echo "SECRET_MASTER_KEY: ";

    $masterKey = read_hidden_input($isWindows);
    echo "\n";

    if ($masterKey === '') {
        fwrite(STDERR, "Chave vazia. Abortando.\n");
        exit(1);
    }

    echo "[..] Persistindo SECRET_MASTER_KEY...\n";
    persist_env_var('SECRET_MASTER_KEY', $masterKey, $isWindows);

    if ($isWindows) {
        echo "[ok] SECRET_MASTER_KEY persistida via setx (escopo do usuário).\n";
        echo "     Feche este terminal e abra um novo para enxergar a variável.\n";
        echo "     Reinicie o Apache (XAMPP) para que ele herde a SECRET_MASTER_KEY.\n";
    } else {
        echo "[ok] SECRET_MASTER_KEY persistida.\n";
        echo "     Shell: abra novo terminal ou rode 'source <arquivo_rc>'.\n";
        echo "     Web (php-fpm): já configurado via pool.d/www.conf se o php-fpm foi detectado.\n";
    }
}

putenv('SECRET_MASTER_KEY=' . $masterKey);
$_ENV['SECRET_MASTER_KEY'] = $masterKey;
$_SERVER['SECRET_MASTER_KEY'] = $masterKey;

if (!file_exists($secretsJson)) {
    if (file_exists($secretsEnc)) {
        echo "[ok] config/secrets.enc já existe e nenhum config/secrets.json para criptografar.\n";
        echo "     Setup concluído.\n";
        exit(0);
    }

    fwrite(STDERR, "[erro] config/secrets.json não encontrado e config/secrets.enc também não existe.\n");
    fwrite(STDERR, "       Crie config/secrets.json a partir de config/secrets.json.example e rode novamente.\n");
    exit(1);
}

echo "[..] Gerando config/secrets.enc a partir de config/secrets.json...\n";

require_once $projectRoot . '/generate-secrets.php';

echo "[ok] Setup concluído.\n";
echo "     Lembre-se de remover config/secrets.json após validar a aplicação.\n";

// ---------------------------------------------------------------------------

function read_master_key_from_env(): string
{
    foreach (['SECRET_MASTER_KEY'] as $name) {
        $candidates = [
            getenv($name),
            $_SERVER[$name] ?? null,
            $_ENV[$name] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }
    }

    return '';
}

function read_hidden_input(bool $isWindows): string
{
    if ($isWindows) {
        $cmd = 'powershell -NoProfile -Command "$p = Read-Host -AsSecureString;'
            . ' [System.Runtime.InteropServices.Marshal]::PtrToStringAuto('
            . '[System.Runtime.InteropServices.Marshal]::SecureStringToBSTR($p))"';
        $value = shell_exec($cmd);

        if ($value === null) {
            fwrite(STDERR, "Falha ao ler entrada via PowerShell.\n");
            exit(1);
        }

        return rtrim($value, "\r\n");
    }

    $sttyAvailable = trim((string) shell_exec('command -v stty')) !== '';

    if ($sttyAvailable) {
        $previous = trim((string) shell_exec('stty -g'));
        shell_exec('stty -echo');
        $value = fgets(STDIN);
        shell_exec('stty ' . $previous);
    } else {
        fwrite(STDERR, "[aviso] stty não disponível, entrada ficará visível.\n");
        $value = fgets(STDIN);
    }

    if ($value === false) {
        return '';
    }

    return rtrim($value, "\r\n");
}

function persist_env_var(string $name, string $value, bool $isWindows): void
{
    if ($isWindows) {
        persist_env_var_windows($name, $value);
        return;
    }

    persist_env_var_unix_shell($name, $value);
    persist_env_var_php_fpm($name, $value);
}

function persist_env_var_windows(string $name, string $value): void
{
    if (strlen($value) > 1024) {
        fwrite(STDERR, "[erro] setx limita valores a 1024 caracteres.\n");
        exit(1);
    }

    if (!preg_match('/^[A-Z_][A-Z0-9_]*$/i', $name)) {
        fwrite(STDERR, "[erro] Nome de variável inválido.\n");
        exit(1);
    }

    $escapedValue = str_replace('"', '""', $value);
    $cmd = 'setx ' . $name . ' "' . $escapedValue . '"';

    $output = [];
    $code = 0;
    exec($cmd . ' 2>&1', $output, $code);

    if ($code !== 0) {
        fwrite(STDERR, "[erro] setx falhou (exit {$code}):\n" . implode("\n", $output) . "\n");
        exit(1);
    }
}

function persist_env_var_unix_shell(string $name, string $value): void
{
    $home = getenv('HOME');
    if (!is_string($home) || $home === '') {
        if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
            $pw = posix_getpwuid(posix_geteuid());
            $home = is_array($pw) && isset($pw['dir']) ? (string) $pw['dir'] : '';
        }
    }

    if ($home === '') {
        fwrite(STDERR, "[erro] Não foi possível resolver o HOME do usuário.\n");
        exit(1);
    }

    $shell = (string) getenv('SHELL');
    $rcFile = str_contains($shell, 'zsh') ? $home . '/.zshrc' : $home . '/.bashrc';

    $marker = '# managed by card-leak-checker setup :: ' . $name;
    $exportLine = sprintf('export %s=%s', $name, escapeshellarg($value));

    $existing = file_exists($rcFile) ? (string) file_get_contents($rcFile) : '';

    if ($existing !== '') {
        $lines = preg_split('/\r?\n/', $existing) ?: [];
        $clean = [];
        $skipNext = false;

        foreach ($lines as $line) {
            if ($skipNext) {
                $skipNext = false;
                continue;
            }

            if (trim($line) === $marker) {
                $skipNext = true;
                continue;
            }

            $clean[] = $line;
        }

        $existing = rtrim(implode("\n", $clean), "\n");
    }

    $payload = $existing === ''
        ? $marker . "\n" . $exportLine . "\n"
        : $existing . "\n\n" . $marker . "\n" . $exportLine . "\n";

    if (file_put_contents($rcFile, $payload) === false) {
        fwrite(STDERR, "[erro] Falha ao escrever em {$rcFile}.\n");
        exit(1);
    }

    echo "     Shell atualizado: {$rcFile}\n";
}

function persist_env_var_php_fpm(string $name, string $value): void
{
    $phpEtcDir = '/etc/php';

    if (!is_dir($phpEtcDir)) {
        return;
    }

    $entries = scandir($phpEtcDir) ?: [];
    $versions = [];

    foreach ($entries as $entry) {
        if (preg_match('/^\d+\.\d+$/', $entry) === 1 && is_dir($phpEtcDir . '/' . $entry . '/fpm')) {
            $versions[] = $entry;
        }
    }

    if (count($versions) === 0) {
        return;
    }

    $any = false;

    foreach ($versions as $version) {
        $poolFile = "{$phpEtcDir}/{$version}/fpm/pool.d/www.conf";

        if (!is_file($poolFile)) {
            continue;
        }

        if (!is_writable($poolFile)) {
            echo "     [aviso] {$poolFile} não é gravável. Rode como root para configurar o php-fpm.\n";
            continue;
        }

        $content = file_get_contents($poolFile);
        if ($content === false) {
            echo "     [aviso] Falha ao ler {$poolFile}.\n";
            continue;
        }

        $beginMarker = '; CARDLEAK_SECRETS_BEGIN';
        $endMarker = '; CARDLEAK_SECRETS_END';

        $pattern = '/\n?' . preg_quote($beginMarker, '/') . '.*?' . preg_quote($endMarker, '/') . '\n?/s';
        $cleaned = preg_replace($pattern, "\n", $content);
        if (!is_string($cleaned)) {
            $cleaned = $content;
        }

        $escapedValue = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
        $block = $beginMarker . "\n"
            . 'env[' . $name . '] = "' . $escapedValue . "\"\n"
            . $endMarker . "\n";

        $newContent = rtrim($cleaned, "\n") . "\n\n" . $block;

        if (file_put_contents($poolFile, $newContent) === false) {
            echo "     [aviso] Falha ao escrever em {$poolFile}.\n";
            continue;
        }

        @chmod($poolFile, 0640);

        echo "     PHP-FPM atualizado: {$poolFile}\n";
        $any = true;

        $serviceName = "php{$version}-fpm";

        if (reload_systemd_service($serviceName)) {
            echo "     Serviço recarregado: {$serviceName}\n";
        } else {
            echo "     [!] Recarregue manualmente: systemctl reload {$serviceName}\n";
            echo "         (ou kill -USR2 \$(cat /run/php/{$serviceName}.pid) se não houver systemd ativo)\n";
        }
    }

    if (!$any) {
        return;
    }
}

function reload_systemd_service(string $serviceName): bool
{
    $check = [];
    $checkCode = 0;
    exec('command -v systemctl 2>/dev/null', $check, $checkCode);

    if ($checkCode !== 0) {
        return false;
    }

    $output = [];
    $code = 0;
    exec('systemctl reload ' . escapeshellarg($serviceName) . ' 2>&1', $output, $code);

    return $code === 0;
}
