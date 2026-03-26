<?php

return [
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'dbname' => env('DB_NAME', 'card_leak_checker'),
    'username' => required_secret('DB_USER'),
    'password' => required_secret('DB_PASS'),
    'charset' => 'utf8mb4',
];