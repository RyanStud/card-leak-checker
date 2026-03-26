<?php

function app_log(string $message): void
{
    $logDir = __DIR__ . '/../../storage/logs';
    $logFile = $logDir . '/app.log';

    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $message = str_replace(["\r", "\n"], [' ', ' '], $message);

    $date = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$date] $message" . PHP_EOL, FILE_APPEND | LOCK_EX);
}