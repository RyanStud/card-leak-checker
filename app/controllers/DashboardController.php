<?php

class DashboardController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::handle();

        $userModel = new User();
        $user = $userModel->findById((int)$_SESSION['user_id']);

        $this->view('dashboard/index', [
            'user' => $user,
        ]);
    }
}