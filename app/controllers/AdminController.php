<?php

if (!class_exists('TelegramAccount')) {
    require_once __DIR__ . '/../models/TelegramAccount.php';
}

if (!function_exists('telegram_send_message')) {
    require_once __DIR__ . '/../helpers/telegram.php';
}

class AdminController extends Controller
{
    public function usersManagement(): void
    {
        AdminMiddleware::handle();

        $emailFilter = trim(clean_text($_GET['email'] ?? ''));
        $page = (int)($_GET['page'] ?? 1);
        if ($page < 1) {
            $page = 1;
        }

        $limit = 12;
        $offset = ($page - 1) * $limit;

        $userModel = new User();
        $users = $userModel->getUsersWithTelegramStatus($emailFilter, $limit, $offset);
        $totalUsers = $userModel->countUsersWithTelegramStatus($emailFilter);
        $totalPages = (int)max(1, ceil($totalUsers / $limit));

        $requestsModel = new AdminRoleChangeRequest();
        $pendingRequests = $requestsModel->getPending(30);

        $this->view('admin/users', [
            'users' => $users,
            'currentUserId' => (int)($_SESSION['user_id'] ?? 0),
            'emailFilter' => $emailFilter,
            'page' => $page,
            'totalPages' => $totalPages,
            'pendingRequests' => $pendingRequests,
        ]);
    }

    public function updateUserRole(): void
    {
        AdminMiddleware::handle();
        verify_csrf();

        $targetUserId = (int)($_POST['target_user_id'] ?? 0);
        $newRole = clean_text($_POST['role'] ?? '');
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);

        if ($targetUserId <= 0 || !in_array($newRole, ['admin', 'user'], true)) {
            set_flash('error', 'Dados invalidos para solicitar alteracao de permissao.');
            $this->redirect(base_path('/admin/users'));
        }

        $userModel = new User();
        $targetUser = $userModel->findById($targetUserId);

        if (!$targetUser) {
            set_flash('error', 'Usuario nao encontrado.');
            $this->redirect(base_path('/admin/users'));
        }

        if ($targetUserId === $currentUserId && $newRole === 'user') {
            set_flash('error', 'Voce nao pode remover sua propria permissao administrativa.');
            $this->redirect(base_path('/admin/users'));
        }

        $currentRole = (string)($targetUser['role'] ?? 'user');
        if ($currentRole === $newRole) {
            set_flash('error', 'Alteracao invalida para o papel atual do usuario.');
            $this->redirect(base_path('/admin/users'));
        }

        if ($currentRole === 'admin' && $newRole !== 'user') {
            set_flash('error', 'Usuario admin pode apenas ser rebaixado para user.');
            $this->redirect(base_path('/admin/users'));
        }

        if ($currentRole === 'user' && $newRole !== 'admin') {
            set_flash('error', 'Usuario comum pode apenas ser promovido para admin.');
            $this->redirect(base_path('/admin/users'));
        }

        if ($newRole === 'admin') {
            $telegramModel = new TelegramAccount();
            $telegram = $telegramModel->findByUserId($targetUserId);

            $hasActiveTelegram = $telegram
                && !empty($telegram['telegram_user_id'])
                && !empty($telegram['is_active']);

            if (!$hasActiveTelegram) {
                set_flash('error', 'Para virar admin, o usuario precisa ter Telegram vinculado e ativo.');
                $this->redirect(base_path('/admin/users'));
            }
        }

        if ($currentRole === 'admin' && $newRole === 'user') {
            $adminCount = $userModel->countAdmins();
            if ($adminCount <= 1) {
                set_flash('error', 'Nao e permitido remover o ultimo admin do sistema.');
                $this->redirect(base_path('/admin/users'));
            }
        }

        $requestsModel = new AdminRoleChangeRequest();

        if ($requestsModel->hasPendingForUser($targetUserId)) {
            set_flash('error', 'Ja existe solicitacao pendente para este usuario.');
            $this->redirect(base_path('/admin/users'));
        }

        $requested = $requestsModel->createRequest($currentUserId, $targetUserId, $currentRole, $newRole);
        if (!$requested) {
            set_flash('error', 'Falha ao enviar solicitacao para aprovacao.');
            $this->redirect(base_path('/admin/users'));
        }

        $audit = new AuditLog();
        $audit->create(
            $currentUserId,
            null,
            'admin_user_role_change_requested',
            json_encode([
                'target_user_id' => $targetUserId,
                'target_email' => $targetUser['email'] ?? null,
                'old_role' => $currentRole,
                'requested_role' => $newRole,
            ], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Solicitacao enviada para aprovacoes.');
        $this->redirect(base_path('/admin/users'));
    }

    public function approveUserRoleChange(): void
    {
        AdminMiddleware::handle();
        verify_csrf();

        $requestId = (int)($_POST['request_id'] ?? 0);
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);

        if ($requestId <= 0) {
            set_flash('error', 'Solicitacao invalida.');
            $this->redirect(base_path('/admin/users'));
        }

        $requestsModel = new AdminRoleChangeRequest();
        $request = $requestsModel->findPendingById($requestId);
        if (!$request) {
            set_flash('error', 'Solicitacao nao encontrada ou ja analisada.');
            $this->redirect(base_path('/admin/users'));
        }

        $targetUserId = (int)$request['target_user_id'];
        $requestedRole = (string)$request['to_role'];

        $userModel = new User();
        $targetUser = $userModel->findById($targetUserId);
        if (!$targetUser) {
            set_flash('error', 'Usuario alvo nao encontrado.');
            $this->redirect(base_path('/admin/users'));
        }

        $currentRole = (string)($targetUser['role'] ?? 'user');
        if ($currentRole === $requestedRole) {
            $requestsModel->markRejected($requestId, $currentUserId);
            set_flash('error', 'Solicitacao invalida: papel ja corresponde ao solicitado.');
            $this->redirect(base_path('/admin/users'));
        }

        if ($requestedRole === 'admin') {
            $telegramModel = new TelegramAccount();
            $telegram = $telegramModel->findByUserId($targetUserId);
            $hasActiveTelegram = $telegram
                && !empty($telegram['telegram_user_id'])
                && !empty($telegram['is_active']);

            if (!$hasActiveTelegram) {
                set_flash('error', 'Aprovacao negada: usuario sem Telegram vinculado/ativo.');
                $this->redirect(base_path('/admin/users'));
            }
        }

        if ($currentRole === 'admin' && $requestedRole === 'user') {
            $adminCount = $userModel->countAdmins();
            if ($adminCount <= 1) {
                set_flash('error', 'Nao e permitido remover o ultimo admin do sistema.');
                $this->redirect(base_path('/admin/users'));
            }
        }

        $saved = $userModel->updateRole($targetUserId, $requestedRole);
        if (!$saved) {
            set_flash('error', 'Falha ao aplicar nova permissao.');
            $this->redirect(base_path('/admin/users'));
        }

        $requestsModel->markApproved($requestId, $currentUserId);

        $audit = new AuditLog();
        $audit->create(
            $currentUserId,
            null,
            'admin_user_role_change_approved',
            json_encode([
                'request_id' => $requestId,
                'target_user_id' => $targetUserId,
                'new_role' => $requestedRole,
            ], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Solicitacao aprovada e permissao aplicada.');
        $this->redirect(base_path('/admin/users'));
    }

    public function rejectUserRoleChange(): void
    {
        AdminMiddleware::handle();
        verify_csrf();

        $requestId = (int)($_POST['request_id'] ?? 0);
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);

        if ($requestId <= 0) {
            set_flash('error', 'Solicitacao invalida.');
            $this->redirect(base_path('/admin/users'));
        }

        $requestsModel = new AdminRoleChangeRequest();
        $request = $requestsModel->findPendingById($requestId);
        if (!$request) {
            set_flash('error', 'Solicitacao nao encontrada ou ja analisada.');
            $this->redirect(base_path('/admin/users'));
        }

        $requestsModel->markRejected($requestId, $currentUserId);

        $audit = new AuditLog();
        $audit->create(
            $currentUserId,
            null,
            'admin_user_role_change_rejected',
            json_encode([
                'request_id' => $requestId,
                'target_user_id' => (int)$request['target_user_id'],
                'requested_role' => $request['to_role'] ?? null,
            ], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Solicitacao rejeitada.');
        $this->redirect(base_path('/admin/users'));
    }

    public function showElevation(): void
    {
        $this->requireAdminRole();

        $elevatedUntil = (int)($_SESSION['admin_elevated_until'] ?? 0);
        $isElevated = $elevatedUntil >= time();
        $telegramMode = strtolower(trim((string)env('TELEGRAM_MODE', 'api')));

        $telegramModel = new TelegramAccount();
        $telegramAccount = $telegramModel->findByUserId((int)$_SESSION['user_id']);
        $telegramLinked = !empty($telegramAccount['telegram_user_id']) && !empty($telegramAccount['is_active']);

        $this->view('admin/elevate', [
            'isElevated' => $isElevated,
            'elevatedUntil' => $elevatedUntil,
            'telegramMode' => $telegramMode,
            'telegramLinked' => $telegramLinked,
            'telegramAccount' => $telegramAccount,
        ]);
    }

    public function sendElevationCode(): void
    {
        $this->requireAdminRole();
        verify_csrf();

        $telegramMode = strtolower(trim((string)env('TELEGRAM_MODE', 'api')));

        if ($telegramMode === 'log') {
            $lastSentAt = (int)($_SESSION['admin_elevation_code_sent_at'] ?? 0);
            if ($lastSentAt > 0 && (time() - $lastSentAt) < 30) {
                set_flash('error', 'Aguarde 30 segundos para solicitar novo codigo de elevacao.');
                $this->redirect(base_path('/admin/elevate'));
            }

            $code = (string)random_int(100000, 999999);
            $_SESSION['admin_elevation_code_hash'] = hash('sha256', $code);
            $_SESSION['admin_elevation_code_expires'] = time() + 300;
            $_SESSION['admin_elevation_code_sent_at'] = time();

            app_log('admin_elevation_log_mode user_id=' . (string)$_SESSION['user_id'] . ' code=' . $code . ' expires_in=300s');

            set_flash('success', 'Codigo de elevacao gerado em modo local. Verifique o arquivo storage/logs/app.log.');
            $this->redirect(base_path('/admin/elevate'));
        }

        $telegramModel = new TelegramAccount();
        $telegramAccount = $telegramModel->findByUserId((int)$_SESSION['user_id']);

        if (!$telegramAccount || empty($telegramAccount['telegram_user_id']) || empty($telegramAccount['is_active'])) {
            set_flash('error', 'Conta Telegram nao vinculada/ativa. Vincule no dashboard antes da elevacao admin.');
            $this->redirect(base_path('/admin/elevate'));
        }

        $lastSentRaw = trim((string)($telegramAccount['login_code_sent_at'] ?? ''));
        if ($lastSentRaw !== '') {
            $lastSentTs = strtotime($lastSentRaw);
            if ($lastSentTs !== false && (time() - $lastSentTs) < 30) {
                set_flash('error', 'Aguarde 30 segundos para solicitar novo codigo de elevacao.');
                $this->redirect(base_path('/admin/elevate'));
            }
        }

        $code = (string)random_int(100000, 999999);
        $saved = $telegramModel->issueLoginCode((int)$_SESSION['user_id'], $code, date('Y-m-d H:i:s', time() + 300));

        if (!$saved) {
            set_flash('error', 'Nao foi possivel preparar o codigo de elevacao.');
            $this->redirect(base_path('/admin/elevate'));
        }

        $sent = telegram_send_message(
            (int)$telegramAccount['telegram_user_id'],
            'Codigo de elevacao admin Card Leak Checker: ' . $code . ' (valido por 5 minutos).'
        );

        if (!$sent) {
            set_flash('error', 'Falha ao enviar codigo no Telegram.');
            $this->redirect(base_path('/admin/elevate'));
        }

        set_flash('success', 'Codigo de elevacao enviado no Telegram.');
        $this->redirect(base_path('/admin/elevate'));
    }

    public function verifyElevationCode(): void
    {
        $this->requireAdminRole();
        verify_csrf();

        $code = clean_numeric_text($_POST['code'] ?? '');
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            set_flash('error', 'Codigo de elevacao invalido.');
            $this->redirect(base_path('/admin/elevate'));
        }

        $telegramMode = strtolower(trim((string)env('TELEGRAM_MODE', 'api')));

        if ($telegramMode === 'log') {
            $expectedHash = (string)($_SESSION['admin_elevation_code_hash'] ?? '');
            $expiresAt = (int)($_SESSION['admin_elevation_code_expires'] ?? 0);

            $ok = ($expectedHash !== '')
                && $expiresAt >= time()
                && hash_equals($expectedHash, hash('sha256', $code));

            if ($ok) {
                unset(
                    $_SESSION['admin_elevation_code_hash'],
                    $_SESSION['admin_elevation_code_expires'],
                    $_SESSION['admin_elevation_code_sent_at']
                );
            }
        } else {
            $telegramModel = new TelegramAccount();
            $ok = $telegramModel->consumeValidLoginCode((int)$_SESSION['user_id'], $code);
        }

        if (!$ok) {
            $suspicious = new SuspiciousEvent();
            $suspicious->create(
                (int)$_SESSION['user_id'],
                null,
                client_ip(),
                'admin_elevation_code_invalid',
                'Tentativa com codigo Telegram de elevacao invalido'
            );

            set_flash('error', 'Codigo incorreto ou expirado.');
            $this->redirect(base_path('/admin/elevate'));
        }

        $ttl = (int)env('ADMIN_ELEVATION_TTL', 900);
        if ($ttl < 60) {
            $ttl = 900;
        }

        $_SESSION['admin_elevated_until'] = time() + $ttl;

        $audit = new AuditLog();
        $audit->create(
            (int)$_SESSION['user_id'],
            null,
            'admin_session_elevated_via_telegram',
            json_encode([
                'ttl_seconds' => $ttl,
                'expires_at' => date('Y-m-d H:i:s', (int)$_SESSION['admin_elevated_until']),
            ], JSON_UNESCAPED_UNICODE)
        );

        set_flash('success', 'Sessao administrativa elevada com sucesso.');
        $this->redirect(base_path('/admin'));
    }

    public function dashboard(): void
    {
        AdminMiddleware::handle();

        $allowedRanges = [
            '24h' => ['seconds' => 24 * 3600, 'label' => 'Últimas 24 horas'],
            '3d' => ['seconds' => 3 * 24 * 3600, 'label' => 'Últimos 3 dias'],
            '7d' => ['seconds' => 7 * 24 * 3600, 'label' => 'Últimos 7 dias'],
            '14d' => ['seconds' => 14 * 24 * 3600, 'label' => 'Últimos 14 dias'],
            '28d' => ['seconds' => 28 * 24 * 3600, 'label' => 'Últimos 28 dias'],
        ];

        $selectedRange = clean_text($_GET['range'] ?? '7d');
        if (!isset($allowedRanges[$selectedRange])) {
            $selectedRange = '7d';
        }

        $since = date('Y-m-d H:i:s', time() - $allowedRanges[$selectedRange]['seconds']);

        $admin = new AdminDashboard();
        $userModel = new User();

        $counts = $admin->getCounts($since);
        $users = $userModel->getAllUsers($since);
        $auditLogs = $admin->getRecentAuditLogs(20, $since);
        $suspiciousEvents = $admin->getRecentSuspiciousEvents(20, $since);
        $loginAttempts = $admin->getRecentLoginAttempts(30, $since);
        $topIps = $admin->getTopIps(10, $since);
        $suspiciousTypes = $admin->getSuspiciousEventTypes($since);
        $criticalSessionEvents = $admin->getRecentCriticalSessionEvents(10, $since);
        $recentCardChecks = $admin->getRecentCardChecks(20, $since);
        $blockedIps = $admin->getBlockedIps(20, $since);
        $recentRequests = $admin->getRecentRequests(30, $since);
        $topCountries = $admin->getTopCountries(10, $since);

        $this->view('admin/dashboard', [
            'counts' => $counts,
            'users' => $users,
            'auditLogs' => $auditLogs,
            'suspiciousEvents' => $suspiciousEvents,
            'loginAttempts' => $loginAttempts,
            'topIps' => $topIps,
            'suspiciousTypes' => $suspiciousTypes,
            'criticalSessionEvents' => $criticalSessionEvents,
            'recentCardChecks' => $recentCardChecks,
            'blockedIps' => $blockedIps,
            'recentRequests' => $recentRequests,
            'topCountries' => $topCountries,
            'selectedRange' => $selectedRange,
            'allowedRanges' => $allowedRanges,
        ]);
    }

    public function importCards(): void
    {
        AdminMiddleware::handle();
        verify_csrf();

        $defaultCsvPath = (string)env('LEAKED_CARDS_SAMPLE_CSV', 'database/sample_leaked_cards.csv');
        $argCsvPath = clean_text($_POST['csv_path'] ?? $defaultCsvPath);
        $sourceBatch = clean_text($_POST['source_batch'] ?? ('admin-' . date('Ymd-His')));

        $csvPath = $argCsvPath === '' ? $defaultCsvPath : $argCsvPath;

        if (!preg_match('/^[A-Za-z]:\\\\/', $csvPath) && !str_starts_with($csvPath, '/')) {
            $csvPath = __DIR__ . '/../../' . ltrim($csvPath, '/\\');
        }

        $realPath = realpath($csvPath);
        if ($realPath === false) {
            set_flash('error', 'CSV nao encontrado para importacao.');
            $this->redirect(base_path('/admin'));
        }

        if ($sourceBatch === '') {
            $sourceBatch = 'admin-' . date('Ymd-His');
        }

        try {
            $vault = new LeakedCardVault();
            $inserted = $vault->importFromCsv($realPath, $sourceBatch);

            $audit = new AuditLog();
            $audit->create(
                (int)($_SESSION['user_id'] ?? 0),
                null,
                'admin_cards_vault_import',
                json_encode([
                    'csv_path' => $realPath,
                    'source_batch' => $sourceBatch,
                    'inserted' => $inserted,
                ], JSON_UNESCAPED_UNICODE)
            );

            set_flash('success', 'Importacao concluida. Registros inseridos: ' . (string)$inserted . '. Lote: ' . $sourceBatch);
        } catch (Throwable $e) {
            error_log('AdminController::importCards: ' . $e->getMessage());
            set_flash('error', 'Falha na importacao. Verifique o arquivo e tente novamente.');
        }

        $this->redirect(base_path('/admin'));
    }

    public function sendTelegramNotice(): void
    {
        AdminMiddleware::handle();
        verify_csrf();

        $message = clean_text($_POST['message'] ?? '');
        $onlyAdmins = ($_POST['only_admins'] ?? '') === '1';

        if ($message === '' || mb_strlen($message) < 5) {
            set_flash('error', 'Digite uma mensagem com pelo menos 5 caracteres.');
            $this->redirect(base_path('/admin'));
        }

        if (mb_strlen($message) > 900) {
            set_flash('error', 'Mensagem muito longa. Limite de 900 caracteres.');
            $this->redirect(base_path('/admin'));
        }

        $telegramModel = new TelegramAccount();
        $recipients = $telegramModel->getNoticeRecipients($onlyAdmins);

        if (empty($recipients)) {
            set_flash('error', 'Nenhum destinatario Telegram ativo encontrado para envio.');
            $this->redirect(base_path('/admin'));
        }

        $sentCount = 0;
        foreach ($recipients as $recipient) {
            $chatId = (int)($recipient['telegram_user_id'] ?? 0);
            if ($chatId <= 0) {
                continue;
            }

            $ok = telegram_send_message($chatId, '[Aviso Admin] ' . $message);
            if ($ok) {
                $sentCount++;
            }
        }

        $audit = new AuditLog();
        $audit->create(
            (int)($_SESSION['user_id'] ?? 0),
            null,
            'admin_telegram_notice_sent',
            json_encode([
                'only_admins' => $onlyAdmins,
                'target_recipients' => count($recipients),
                'sent_count' => $sentCount,
            ], JSON_UNESCAPED_UNICODE)
        );

        if ($sentCount === 0) {
            set_flash('error', 'Falha ao enviar avisos Telegram.');
            $this->redirect(base_path('/admin'));
        }

        set_flash('success', 'Aviso Telegram enviado para ' . (string)$sentCount . ' destinatario(s).');
        $this->redirect(base_path('/admin'));
    }

    private function requireAdminRole(): void
    {
        AdminMiddleware::requireAdminRole();
    }
}