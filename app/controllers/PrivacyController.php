<?php

class PrivacyController extends Controller
{
    public function saveCookieConsent(): void
    {
        verify_csrf();

        $mode = clean_text($_POST['mode'] ?? 'essential');
        $analytics = $mode === 'all';

        cookie_consent_set($analytics);

        $redirectTo = clean_text($_POST['redirect_to'] ?? '');
        if ($redirectTo === '' || !str_starts_with($redirectTo, '/')) {
            $redirectTo = '/';
        }

        set_flash('success', 'Preferências de cookies salvas com sucesso.');
        $this->redirect(base_path($redirectTo));
    }

    public function about(): void
    {
        $this->view('about/index');
    }

    public function terms(): void
    {
        $this->view('terms/index');
    }

    public function index(): void
    {
        AuthMiddleware::handle();

        $userId = (int)$_SESSION['user_id'];

        $privacyModel = new Privacy();

        $profile = $privacyModel->getUserProfileSummary($userId);
        $historyCount = $privacyModel->countUserHistory($userId);
        $projectsCount = $privacyModel->countOwnedProjects($userId);
        $securityCount = 0;
        $securityModel = new UserSecurityAnswer();
        $securityCount = $securityModel->countUserAnswers($userId);
        $securityIndices = $securityModel->getUserQuestionIndices($userId);

        $this->view('privacy/index', [
            'profile' => $profile,
            'historyCount' => $historyCount,
            'projectsCount' => $projectsCount,
            'securityCount' => $securityCount,
            'securityIndices' => $securityIndices,
            'removableFields' => $privacyModel->removableFields(),
        ]);
    }

    public function deleteField(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $userId = (int)$_SESSION['user_id'];
        $field = clean_text($_POST['field'] ?? '');

        $privacyModel = new Privacy();

        if (!in_array($field, $privacyModel->removableFields(), true)) {
            set_flash('error', 'Campo inválido para remoção.');
            $this->redirect(base_path('/privacy'));
        }

        $privacyModel->clearUserField($userId, $field);

        $audit = new AuditLog();
        $audit->create(
            $userId,
            null,
            'lgpd_delete_field',
            json_encode(['field' => $field], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Dado removido com sucesso.');
        $this->redirect(base_path('/privacy'));
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

    public function saveSecurityQuestions(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $userId = (int)$_SESSION['user_id'];

        require_once __DIR__ . '/../helpers/security_questions.php';

        $questions = security_questions_list();

        $answers = [];
        foreach ($questions as $index => $_) {
            $field = 'q_' . (string)$index;
            if (isset($_POST[$field]) && trim((string)$_POST[$field]) !== '') {
                $answers[$index] = trim((string)$_POST[$field]);
            }
        }

        if (count($answers) !== 5) {
            set_flash('error', 'Responda exatamente 5 perguntas.');
            $this->redirect(base_path('/privacy'));
        }

        $model = new UserSecurityAnswer();
        $saved = $model->saveAnswers($userId, $answers);

        if (!$saved) {
            set_flash('error', 'Erro ao salvar suas respostas. Tente novamente.');
            $this->redirect(base_path('/privacy'));
        }

        $audit = new AuditLog();
        $audit->create($userId, null, 'security_questions_saved', json_encode(array_keys($answers), JSON_UNESCAPED_UNICODE));

        set_flash('success', 'Respostas de segurança salvas com sucesso.');
        $this->redirect(base_path('/privacy'));
    }
}