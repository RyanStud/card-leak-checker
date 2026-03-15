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

        if ($name === '' || mb_strlen($name) < 3) {
            set_flash('error', 'Nome do projeto inválido.');
            $this->redirect(base_path('/projects'));
        }

        if (!in_array($privacyMode, ['private', 'restricted'], true)) {
            $privacyMode = 'private';
        }

        $slugBase = generate_slug($name);
        $slug = $slugBase . '-' . time();

        $projectModel = new Project();
        $projectModel->create($name, $slug, (int)$_SESSION['user_id'], $privacyMode);

        $audit = new AuditLog();
        $audit->create(
            (int)$_SESSION['user_id'],
            null,
            'project_created',
            json_encode(['name' => $name, 'slug' => $slug], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Projeto criado com sucesso.');
        $this->redirect(base_path('/projects'));
    }
}