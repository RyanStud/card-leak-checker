<?php

class PrivacyController extends Controller
{
    public function about(): void
    {
        $this->view('about/index');
    }

    public function index(): void
    {
        AuthMiddleware::handle();

        $userId = (int)$_SESSION['user_id'];

        $privacyModel = new Privacy();

        $profile = $privacyModel->getUserProfileSummary($userId);
        $historyCount = $privacyModel->countUserHistory($userId);
        $projectsCount = $privacyModel->countOwnedProjects($userId);

        $this->view('privacy/index', [
            'profile' => $profile,
            'historyCount' => $historyCount,
            'projectsCount' => $projectsCount,
        ]);
    }

    public function deleteHistory(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $userId = (int)$_SESSION['user_id'];

        $privacyModel = new Privacy();
        $privacyModel->deleteUserHistory($userId);

        $audit = new AuditLog();
        $audit->create(
            $userId,
            null,
            'lgpd_delete_history',
            json_encode(['message' => 'Histórico de verificações apagado'], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Seu histórico de verificações foi apagado com sucesso.');
        $this->redirect(base_path('/privacy'));
    }

    public function deleteProjects(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $userId = (int)$_SESSION['user_id'];

        $privacyModel = new Privacy();
        $privacyModel->deleteOwnedProjects($userId);

        $audit = new AuditLog();
        $audit->create(
            $userId,
            null,
            'lgpd_delete_projects',
            json_encode(['message' => 'Projetos próprios apagados'], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Seus projetos próprios foram apagados com sucesso.');
        $this->redirect(base_path('/privacy'));
    }

    public function deleteAccount(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $userId = (int)$_SESSION['user_id'];
        $password = $_POST['password'] ?? '';
        $code = clean_numeric_text($_POST['code'] ?? '');

        $userModel = new User();
        $user = $userModel->findById($userId);

        if (!$user) {
            logout_user();
            $this->redirect(base_path('/login'));
        }

        if (!password_verify($password, $user['password_hash'])) {
            set_flash('error', 'Senha incorreta para exclusão da conta.');
            $this->redirect(base_path('/privacy'));
        }

        if (empty($user['two_factor_secret']) || !verify_totp_code($user['two_factor_secret'], $code)) {
            set_flash('error', 'Código do Google Authenticator inválido.');
            $this->redirect(base_path('/privacy'));
        }

        $privacyModel = new Privacy();

        $privacyModel->anonymizeAuditLogs($userId);

        $privacyModel->deleteUserAccount($userId);

        logout_user();

        session_start();
        set_flash('success', 'Conta excluída com sucesso.');

        $this->redirect(base_path('/login'));
    }
}