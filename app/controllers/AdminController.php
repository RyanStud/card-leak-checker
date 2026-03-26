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
}