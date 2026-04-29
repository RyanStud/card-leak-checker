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
        $expiryMonth = clean_numeric_text($_POST['expiry_month'] ?? '');
        $expiryYear = clean_numeric_text($_POST['expiry_year'] ?? '');
        $cvv = clean_numeric_text($_POST['cvv'] ?? '');

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
            set_flash('error', 'Número de cartão inválido.');
            $this->redirect(base_path('/check-card'));
        }

        if (!luhn_is_valid($digits)) {
            set_flash('error', 'O cartão informado falhou na validação Luhn.');
            $this->redirect(base_path('/check-card'));
        }

        $expiryMonth = str_pad(substr($expiryMonth, 0, 2), 2, '0', STR_PAD_LEFT);
        $expiryYear = substr($expiryYear, 0, 4);
        $cvv = substr($cvv, 0, 4);

        if (preg_match('/^\d{2}$/', $expiryMonth) !== 1 || (int)$expiryMonth < 1 || (int)$expiryMonth > 12) {
            set_flash('error', 'Mês de validade inválido.');
            $this->redirect(base_path('/check-card'));
        }

        if (preg_match('/^\d{4}$/', $expiryYear) !== 1) {
            set_flash('error', 'Ano de validade inválido.');
            $this->redirect(base_path('/check-card'));
        }

        if (preg_match('/^\d{3,4}$/', $cvv) !== 1) {
            set_flash('error', 'CVV inválido.');
            $this->redirect(base_path('/check-card'));
        }

        $binMasked = mask_bin($digits);
        $last4Masked = mask_last4($digits);
        $fingerprint = card_fingerprint($digits);

        try {
            $vault = new LeakedCardVault();
            $isLeaked = $vault->isLeakedCard($digits, $expiryMonth, $expiryYear, $cvv);
        } catch (Throwable $e) {
            set_flash('error', 'Base de vazamentos indisponível no momento. Verifique a configuração do vault.');
            $this->redirect(base_path('/check-card'));
        }
        $resultStatus = $isLeaked ? 'possible_leak_found' : 'no_evidence_found';

        $cardCheckModel = new CardCheckRequest();
        $cardCheckModel->create(
            (int)$_SESSION['user_id'],
            $projectId,
            $fingerprint,
            $binMasked,
            $last4Masked,
            $resultStatus,
            'encrypted-vault'
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
                'luhn_validated' => true,
                'expiry_month' => $expiryMonth,
                'expiry_year' => $expiryYear
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
