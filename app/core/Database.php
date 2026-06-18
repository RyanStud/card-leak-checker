<?php

class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../../config/database.php';

            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'],
                $config['port'],
                $config['dbname'],
                $config['charset']
            );

            self::$connection = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            // Alinha o fuso da sessão MySQL com o do PHP (APP_TIMEZONE).
            // Sem isto, NOW() do MySQL e time()/date() do PHP divergem quando o
            // servidor MySQL está em UTC, quebrando comparações de expiração e
            // rate-limit (ex.: código do Telegram que "expira" na hora e o
            // "aguarde 30s" que nunca passa, links de confirmação/reset, etc.).
            // Usa offset numérico (ex.: -03:00), que independe das tabelas de
            // timezone do MySQL estarem carregadas.
            $appTimezone = function_exists('env')
                ? (string) env('APP_TIMEZONE', 'America/Sao_Paulo')
                : 'America/Sao_Paulo';

            try {
                $offset = (new DateTime('now', new DateTimeZone($appTimezone)))->format('P');
                self::$connection->exec("SET time_zone = '" . $offset . "'");
            } catch (\Throwable $e) {
                // Timezone inválido/indisponível: mantém a conexão sem ajuste.
            }
        }

        return self::$connection;
    }
}