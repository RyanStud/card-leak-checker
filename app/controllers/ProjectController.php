<?php

class ProjectController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::handle();

        $projectModel = new Project();
        $projects = $projectModel->getProjectsByUserId((int)$_SESSION['user_id']);

        $this->view('projects/index', [
            'projects' => $projects
        ]);
    }

    public function create(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $name = clean_text($_POST['name'] ?? '');
        $privacyMode = clean_text($_POST['privacy_mode'] ?? 'private');
        $justification = clean_text($_POST['justification'] ?? '');
        $termsAccepted = ($_POST['terms_agree'] ?? '') === '1';

        if ($name === '' || mb_strlen($name) < 3) {
            set_flash('error', 'Nome do projeto inválido.');
            $this->redirect(base_path('/projects'));
        }

        if ($justification === '' || mb_strlen($justification) < 20) {
            set_flash('error', 'Informe uma justificativa com pelo menos 20 caracteres para criar o projeto.');
            $this->redirect(base_path('/projects'));
        }

        if (!$termsAccepted) {
            set_flash('error', 'Você precisa concordar com as condições para criar o projeto.');
            $this->redirect(base_path('/projects'));
        }

        if (!in_array($privacyMode, ['private', 'restricted'], true)) {
            $privacyMode = 'private';
        }

        $slugBase = generate_slug($name);
        $slug = $slugBase . '-' . time();

        $projectModel = new Project();
        $projectModel->createWithJustification($name, $slug, (int)$_SESSION['user_id'], $privacyMode, $justification);

        $audit = new AuditLog();
        $audit->create(
            (int)$_SESSION['user_id'],
            null,
            'project_created',
            json_encode([
                'name' => $name,
                'slug' => $slug,
                'privacy_mode' => $privacyMode,
                'justification' => $justification,
                'terms_accepted' => true,
            ], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Projeto criado com sucesso.');
        $this->redirect(base_path('/projects'));
    }

    public function share(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $projectId = (int)($_POST['project_id'] ?? 0);
        $email = clean_email($_POST['email'] ?? '');

        if ($projectId <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'Dados inválidos para compartilhamento.');
            $this->redirect(base_path('/projects'));
        }

        $projectModel = new Project();
        $project = $projectModel->findById($projectId);

        if (!$project) {
            set_flash('error', 'Projeto não encontrado.');
            $this->redirect(base_path('/projects'));
        }

        if ((int)$project['owner_user_id'] !== (int)$_SESSION['user_id']) {
            set_flash('error', 'Apenas o dono pode compartilhar este projeto.');
            $this->redirect(base_path('/projects'));
        }

        if (($project['privacy_mode'] ?? 'private') !== 'restricted') {
            set_flash('error', 'Somente projetos restritos podem ser compartilhados.');
            $this->redirect(base_path('/projects'));
        }

        $userModel = new User();
        $targetUser = $userModel->findByEmail($email);

        if (!$targetUser) {
            set_flash('error', 'Usuário com este e-mail não foi encontrado.');
            $this->redirect(base_path('/projects'));
        }

        $targetUserId = (int)$targetUser['id'];

        if ($targetUserId === (int)$_SESSION['user_id']) {
            set_flash('error', 'Você já é o dono deste projeto.');
            $this->redirect(base_path('/projects'));
        }

        if ($projectModel->userHasAccess($projectId, $targetUserId)) {
            set_flash('error', 'Este usuário já possui acesso ao projeto.');
            $this->redirect(base_path('/projects'));
        }

        $projectModel->addMember($projectId, $targetUserId, 'member');

        $audit = new AuditLog();
        $audit->create(
            (int)$_SESSION['user_id'],
            $projectId,
            'project_shared',
            json_encode([
                'shared_with_user_id' => $targetUserId,
                'shared_with_email' => $targetUser['email'] ?? $email,
            ], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Projeto compartilhado com sucesso.');
        $this->redirect(base_path('/projects'));
    }

    public function approval(): void
    {
        AdminMiddleware::handle();

        $projectModel = new Project();
        $projects = $projectModel->getPendingProjects();

        $this->view('admin/pending-projects', [
            'projects' => $projects
        ]);
    }

    public function approve(): void
    {
        AdminMiddleware::handle();
        verify_csrf();

        $projectId = (int)($_POST['project_id'] ?? 0);

        if ($projectId <= 0) {
            set_flash('error', 'Projeto inválido.');
            $this->redirect(base_path('/projects/approval'));
        }

        $projectModel = new Project();
        $project = $projectModel->findById($projectId);

        if (!$project) {
            set_flash('error', 'Projeto não encontrado.');
            $this->redirect(base_path('/projects/approval'));
        }

        $projectModel->approveProject($projectId, (int)$_SESSION['user_id']);

        $audit = new AuditLog();
        $audit->create(
            (int)$_SESSION['user_id'],
            $projectId,
            'project_approved',
            json_encode([
                'project_name' => $project['name'] ?? '',
                'owner_id' => $project['owner_user_id'] ?? 0,
            ], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Projeto aprovado com sucesso.');
        $this->redirect(base_path('/projects/approval'));
    }

    public function reject(): void
    {
        AdminMiddleware::handle();
        verify_csrf();

        $projectId = (int)($_POST['project_id'] ?? 0);
        $rejectionReason = clean_text($_POST['rejection_reason'] ?? '');

        if ($projectId <= 0) {
            set_flash('error', 'Projeto inválido.');
            $this->redirect(base_path('/projects/approval'));
        }

        if ($rejectionReason === '') {
            set_flash('error', 'Informe um motivo para a rejeição.');
            $this->redirect(base_path('/projects/approval'));
        }

        $projectModel = new Project();
        $project = $projectModel->findById($projectId);

        if (!$project) {
            set_flash('error', 'Projeto não encontrado.');
            $this->redirect(base_path('/projects/approval'));
        }

        $projectModel->rejectProject($projectId, (int)$_SESSION['user_id'], $rejectionReason);

        $audit = new AuditLog();
        $audit->create(
            (int)$_SESSION['user_id'],
            $projectId,
            'project_rejected',
            json_encode([
                'project_name' => $project['name'] ?? '',
                'owner_id' => $project['owner_user_id'] ?? 0,
                'rejection_reason' => $rejectionReason,
            ], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Projeto rejeitado com sucesso.');
        $this->redirect(base_path('/projects/approval'));
    }

    public function approval_history(): void
    {
        AdminMiddleware::handle();

        $page = (int)($_GET['page'] ?? 1);
        $page = max(1, $page);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $projectModel = new Project();
        $history = $projectModel->getApprovalHistory($limit, $offset);
        $total = $projectModel->getApprovalHistoryCount();
        $totalPages = ceil($total / $limit);

        $this->view('admin/approval-history', [
            'history' => $history,
            'page' => $page,
            'total' => $total,
            'totalPages' => $totalPages,
            'limit' => $limit,
        ]);
    }
}