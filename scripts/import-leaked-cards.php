<?php

declare(strict_types=1);

require __DIR__ . '/../app/core/Env.php';
Env::load(__DIR__ . '/../.env');

require __DIR__ . '/../app/core/SecretManager.php';
require __DIR__ . '/../app/core/Config.php';
require __DIR__ . '/../app/core/Database.php';

require __DIR__ . '/../app/helpers/env.php';
require __DIR__ . '/../app/helpers/security.php';

require __DIR__ . '/../app/models/LeakedCardVault.php';

Config::init();

$defaultCsvPath = (string)env('LEAKED_CARDS_SAMPLE_CSV', 'database/sample_leaked_cards.csv');
$argCsvPath = $argv[1] ?? $defaultCsvPath;
$sourceBatch = $argv[2] ?? ('sample-' . date('Ymd-His'));

$csvPath = $argCsvPath;
if (!preg_match('/^[A-Za-z]:\\\\/', $csvPath) && !str_starts_with($csvPath, '/')) {
    $csvPath = __DIR__ . '/../' . ltrim($csvPath, '/\\');
}

$realPath = realpath($csvPath);
if ($realPath === false) {
    fwrite(STDERR, "CSV nao encontrado: {$csvPath}\n");
    exit(1);
}

try {
    $vault = new LeakedCardVault();
    $inserted = $vault->importFromCsv($realPath, $sourceBatch);
} catch (Throwable $e) {
    error_log('import-leaked-cards: ' . $e->getMessage());
    fwrite(STDERR, "Falha na importacao (detalhe no log de erros).\n");
    exit(1);
}

echo "Importacao concluida.\n";
echo "Arquivo: {$realPath}\n";
echo "Lote: {$sourceBatch}\n";
echo "Registros inseridos: {$inserted}\n";
