<?php

class Config
{
    private static ?SecretManager $secretManager = null;
    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        $secretsFile = (string) Env::get('SECRETS_FILE', 'config/secrets.enc');
        $masterKey = self::readMasterKey();

        if ($masterKey !== '') {
            $fullPath = self::projectPath($secretsFile);

            if (file_exists($fullPath)) {
                self::$secretManager = new SecretManager($fullPath, $masterKey);
            }
        }

        self::$initialized = true;
    }

    public static function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }

    public static function secret(string $key, mixed $default = null): mixed
    {
        if (self::$secretManager !== null && self::$secretManager->has($key)) {
            return self::$secretManager->get($key, $default);
        }

        return $default;
    }

    public static function requireSecret(string $key): mixed
    {
        if (self::$secretManager !== null && self::$secretManager->has($key)) {
            return self::$secretManager->get($key);
        }

        throw new RuntimeException("Segredo obrigatório ausente: {$key}");
    }

    private static function readMasterKey(): string
    {
        $envValue = getenv('SECRET_MASTER_KEY');
        if (is_string($envValue) && trim($envValue) !== '') {
            return trim($envValue);
        }

        $serverValue = $_SERVER['SECRET_MASTER_KEY'] ?? null;
        if (is_string($serverValue) && trim($serverValue) !== '') {
            return trim($serverValue);
        }

        $envArrayValue = $_ENV['SECRET_MASTER_KEY'] ?? null;
        if (is_string($envArrayValue) && trim($envArrayValue) !== '') {
            return trim($envArrayValue);
        }

        $masterKeyFile = Env::get('MASTER_KEY_FILE', '');

        if (is_string($masterKeyFile) && trim($masterKeyFile) !== '') {
            $fullPath = self::isAbsolutePath($masterKeyFile)
                ? $masterKeyFile
                : self::projectPath($masterKeyFile);

            if (file_exists($fullPath)) {
                $content = file_get_contents($fullPath);

                if ($content !== false && trim($content) !== '') {
                    return trim($content);
                }
            }
        }

        return '';
    }

    private static function isAbsolutePath(string $path): bool
    {
        return preg_match('/^[A-Za-z]:\\\\/', $path) === 1 || str_starts_with($path, '/');
    }

    private static function projectPath(string $relativePath): string
    {
        return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . ltrim($relativePath, '/\\');
    }
}