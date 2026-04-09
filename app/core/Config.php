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
            $fullPath = self::resolveProjectFile($secretsFile);

            if ($fullPath !== null) {
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
            $fullPath = self::resolveReadableFile($masterKeyFile);

            if ($fullPath !== null) {
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

    private static function resolveProjectFile(string $relativePath): ?string
    {
        $candidatePath = self::projectPath($relativePath);
        $realPath = realpath($candidatePath);

        if ($realPath === false || !is_file($realPath)) {
            return null;
        }

        $projectRoot = self::normalizePath(self::projectRoot()) . DIRECTORY_SEPARATOR;
        $resolvedPath = self::normalizePath($realPath);

        if (!str_starts_with($resolvedPath, $projectRoot)) {
            return null;
        }

        return $realPath;
    }

    private static function resolveReadableFile(string $path): ?string
    {
        if (!self::isAbsolutePath($path)) {
            return null;
        }

        $candidatePath = $path;

        $realPath = realpath($candidatePath);

        if ($realPath === false || !is_file($realPath)) {
            return null;
        }

        return $realPath;
    }

    private static function projectRoot(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function normalizePath(string $path): string
    {
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        return rtrim($normalizedPath, DIRECTORY_SEPARATOR);
    }
}