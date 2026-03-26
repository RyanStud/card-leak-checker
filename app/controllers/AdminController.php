<?php

class AdminController extends Controller
{
    public function dashboard(): void
    {
        AdminMiddleware::handle();

        $admin = new AdminDashboard();
        $userModel = new User();

        $counts = $admin->getCounts();
        $users = $userModel->getAllUsers();
        $auditLogs = $admin->getRecentAuditLogs(20);
        $suspiciousEvents = $admin->getRecentSuspiciousEvents(20);
        $loginAttempts = $admin->getRecentLoginAttempts(30);
        $topIps = $admin->getTopIps(10);
        $suspiciousTypes = $admin->getSuspiciousEventTypes();
        $recentCardChecks = $admin->getRecentCardChecks(20);
        $blockedIps = $admin->getBlockedIps(20);
        $recentRequests = $admin->getRecentRequests(30);
        $topCountries = $admin->getTopCountries(10);

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
        ]);
    }
}