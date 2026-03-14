<?php

class AuthMiddleware
{
    public static function handle(): void
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: /card-leak-checker/public/login');
            exit;
        }

        if (empty($_SESSION['two_factor_verified'])) {
            header('Location: /card-leak-checker/public/2fa/verify');
            exit;
        }
    }
}