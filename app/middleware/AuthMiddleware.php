<?php

class AuthMiddleware
{
    public static function handle(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . base_path('/login'));
            exit;
        }

        if (empty($_SESSION['two_factor_verified'])) {
            header('Location: ' . base_path('/2fa/verify'));
            exit;
        }
    }
}
