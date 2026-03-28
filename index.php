<?php

require __DIR__ . '/app/core/Env.php';
Env::load(__DIR__ . '/.env');

require __DIR__ . '/app/core/SecretManager.php';
require __DIR__ . '/app/core/Config.php';

require __DIR__ . '/app/helpers/env.php';
require __DIR__ . '/app/helpers/url.php';

Config::init();

$debug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOL);
$appEnv = (string) env('APP_ENV', 'production');

if ($debug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
}

$remoteAddr = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$trustedProxiesRaw = (string)env('TRUSTED_PROXIES', '');
$trustedProxies = array_filter(array_map('trim', explode(',', $trustedProxiesRaw)));

$ipMatchesCidr = static function (string $ip, string $cidr): bool {
    [$subnet, $maskBits] = array_pad(explode('/', $cidr, 2), 2, null);

    if ($subnet === null || $maskBits === null || !is_numeric($maskBits)) {
        return false;
    }

    $mask = (int)$maskBits;
    $ipLong = ip2long($ip);
    $subnetLong = ip2long($subnet);

    if ($ipLong === false || $subnetLong === false || $mask < 0 || $mask > 32) {
        return false;
    }

    $maskLong = $mask === 0 ? 0 : (-1 << (32 - $mask));
    return ($ipLong & $maskLong) === ($subnetLong & $maskLong);
};

$isFromTrustedProxy = false;
foreach ($trustedProxies as $entry) {
    if ($entry === $remoteAddr) {
        $isFromTrustedProxy = true;
        break;
    }

    if (str_contains($entry, '/') && $ipMatchesCidr($remoteAddr, $entry)) {
        $isFromTrustedProxy = true;
        break;
    }
}

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
if (!$isHttps && $isFromTrustedProxy) {
    $forwardedProto = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    $cfVisitor = (string)($_SERVER['HTTP_CF_VISITOR'] ?? '');

    if ($forwardedProto === 'https' || str_contains($cfVisitor, '"https"')) {
        $isHttps = true;
    }
}

$forceHttps = filter_var(env('FORCE_HTTPS', $appEnv === 'production'), FILTER_VALIDATE_BOOL);
if ($forceHttps && !$isHttps && !headers_sent()) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';

    if ($host !== '') {
        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data: https://api.qrserver.com; style-src 'self' 'unsafe-inline'; script-src 'self'; connect-src 'self' https://viacep.com.br;");

if ($isHttps) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

$sessionName = env('SESSION_NAME', 'cardleak_session');
session_name($sessionName);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();

require __DIR__ . '/app/core/Database.php';
require __DIR__ . '/app/core/Controller.php';
require __DIR__ . '/app/core/Router.php';

require __DIR__ . '/app/helpers/view.php';
require __DIR__ . '/app/helpers/auth.php';
require __DIR__ . '/app/helpers/csrf.php';
require __DIR__ . '/app/helpers/otp.php';
require __DIR__ . '/app/helpers/security.php';
require __DIR__ . '/app/helpers/logger.php';
require __DIR__ . '/app/helpers/mailer.php';
require __DIR__ . '/app/helpers/security_view.php';

require __DIR__ . '/app/middleware/AuthMiddleware.php';
require __DIR__ . '/app/middleware/AdminMiddleware.php';
require __DIR__ . '/app/middleware/SecurityMiddleware.php';

require __DIR__ . '/app/models/User.php';
require __DIR__ . '/app/models/Project.php';
require __DIR__ . '/app/models/CardCheckRequest.php';
require __DIR__ . '/app/models/AuditLog.php';
require __DIR__ . '/app/models/Privacy.php';
require __DIR__ . '/app/models/LoginAttempt.php';
require __DIR__ . '/app/models/SuspiciousEvent.php';
require __DIR__ . '/app/models/PasswordReset.php';
require __DIR__ . '/app/models/EmailVerification.php';
require __DIR__ . '/app/models/AdminDashboard.php';
require __DIR__ . '/app/models/SecurityMonitor.php';

require __DIR__ . '/app/controllers/AuthController.php';
require __DIR__ . '/app/controllers/DashboardController.php';
require __DIR__ . '/app/controllers/ProjectController.php';
require __DIR__ . '/app/controllers/CardController.php';
require __DIR__ . '/app/controllers/PrivacyController.php';
require __DIR__ . '/app/controllers/AdminController.php';

SecurityMiddleware::handle();

$router = new Router();

$router->get('/', [AuthController::class, 'showLogin']);

$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);

$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/register/confirmation', [AuthController::class, 'showRegisterConfirmation']);
$router->get('/confirm-email', [AuthController::class, 'confirmEmail']);

$router->get('/2fa/setup', [AuthController::class, 'showSetup2FA']);
$router->post('/2fa/setup', [AuthController::class, 'setup2FA']);

$router->get('/2fa/verify', [AuthController::class, 'showVerify2FA']);
$router->post('/2fa/verify', [AuthController::class, 'verify2FA']);

$router->get('/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);

$router->get('/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

$router->post('/logout', [AuthController::class, 'logout']);

$router->get('/dashboard', [DashboardController::class, 'index']);
$router->post('/dashboard/profile', [DashboardController::class, 'updateProfile']);
$router->post('/dashboard/password', [DashboardController::class, 'updatePassword']);

$router->get('/projects', [ProjectController::class, 'index']);
$router->post('/projects', [ProjectController::class, 'create']);
$router->post('/projects/share', [ProjectController::class, 'share']);
$router->post('/projects/revoke-access', [ProjectController::class, 'revokeAccess']);
$router->get('/projects/approval', [ProjectController::class, 'approval']);
$router->get('/projects/approval-history', [ProjectController::class, 'approval_history']);
$router->post('/projects/approve', [ProjectController::class, 'approve']);
$router->post('/projects/reject', [ProjectController::class, 'reject']);

$router->get('/check-card', [CardController::class, 'showForm']);
$router->post('/check-card', [CardController::class, 'check']);
$router->get('/cards/history', [CardController::class, 'history']);

$router->get('/privacy', [PrivacyController::class, 'index']);
$router->post('/privacy/delete-history', [PrivacyController::class, 'deleteHistory']);
$router->post('/privacy/delete-projects', [PrivacyController::class, 'deleteProjects']);
$router->post('/privacy/delete-account', [PrivacyController::class, 'deleteAccount']);

$router->get('/admin', [AdminController::class, 'dashboard']);

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);