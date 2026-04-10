<?php

class AdminController extends Controller
{
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
            set_flash('error', 'Falha na importacao: ' . $e->getMessage());
        }

        $this->redirect(base_path('/admin'));
    }
}