<?php

return [
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'dbname' => env('DB_NAME', 'card_leak_checker'),
    'username' => secret('DB_USER', env('DB_USER', 'root')),
    'password' => secret('DB_PASS', env('DB_PASS', '')),

    'charset' => 'utf8mb4',
];