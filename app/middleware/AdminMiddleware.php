<?php

class AdminMiddleware
{
    public static function handle(): void
    {
        AuthMiddleware::handle();

        $userModel = new User();
        $user = $userModel->findById((int)$_SESSION['user_id']);

        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            http_response_code(403);
            exit('Acesso restrito ao administrador.');
        }
    }
}