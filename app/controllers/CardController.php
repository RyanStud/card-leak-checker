<?php

class CardController extends Controller
{
    public function showForm(): void
    {
        AuthMiddleware::handle();

        $projectModel = new Project();
        $projects = $projectModel->getProjectsByUserId((int)$_SESSION['user_id']);

        $this->view('cards/check', [
            'projects' => $projects
        ]);
    }

    public function check(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $projectId = (int)($_POST['project_id'] ?? 0);
        $cardNumber = clean_numeric_text($_POST['card_number'] ?? '');

        $projectModel = new Project();

        if (!$projectModel->userHasAccess($projectId, (int)$_SESSION['user_id'])) {
            set_flash('error', 'Você não tem acesso a esse projeto.');
            $this->redirect(base_path('/check-card'));
        }

        $project = $projectModel->findById($projectId);

        if (!$project) {
            set_flash('error', 'Projeto não encontrado.');
            $this->redirect(base_path('/check-card'));
        }

        if ($project['approval_status'] !== 'approved') {
            if ($project['approval_status'] === 'pending') {
                set_flash('error', 'Este projeto ainda está aguardando aprovação do administrador. Você não poderá fazer verificações até que seja aprovado.');
            } elseif ($project['approval_status'] === 'rejected') {
                $reason = $project['rejection_reason'] ?? 'Não informado';
                set_flash('error', 'Este projeto foi rejeitado pelo administrador. Motivo: ' . e($reason));
            }
            $this->redirect(base_path('/check-card'));
        }

        $digits = card_digits_only($cardNumber);

        if (!looks_like_valid_card($digits)) {
            set_flash('error', 'Número de cartão inválido para demonstração.');
            $this->redirect(base_path('/check-card'));
        }

        if (!luhn_is_valid($digits)) {
            set_flash('error', 'O cartão informado falhou na validação Luhn.');
            $this->redirect(base_path('/check-card'));
        }

        $binMasked = mask_bin($digits);
        $last4Masked = mask_last4($digits);
        $fingerprint = card_fingerprint($digits);
        $resultStatus = demo_card_leak_check($digits);

        $cardCheckModel = new CardCheckRequest();
        $cardCheckModel->create(
            (int)$_SESSION['user_id'],
            $projectId,
            $fingerprint,
            $binMasked,
            $last4Masked,
            $resultStatus,
            'demo-local'
        );

        $audit = new AuditLog();
        $audit->create(
            (int)$_SESSION['user_id'],
            $projectId,
            'card_check_requested',
            json_encode([
                'bin_masked' => $binMasked,
                'last4' => $last4Masked,
                'result' => $resultStatus,
                'luhn_validated' => true
            ], JSON_UNESCAPED_UNICODE)
        );

        $_SESSION['check_result'] = [
            'project_id' => $projectId,
            'bin_masked' => $binMasked,
            'last4_masked' => $last4Masked,
            'result_status' => $resultStatus,
            'checked_at' => date('Y-m-d H:i:s')
        ];

        $this->redirect(base_path('/check-card'));
    }

    public function history(): void
    {
        AuthMiddleware::handle();

        $cardCheckModel = new CardCheckRequest();
        $history = $cardCheckModel->getByUserProjects((int)$_SESSION['user_id']);

        $this->view('cards/history', [
            'history' => $history
        ]);
    }
}