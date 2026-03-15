<?php

class AuthController extends Controller
{
    public function showRegister(): void
    {
        $this->view('auth/register');
    }

    public function register(): void
    {
        verify_csrf();

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $_SESSION['old'] = [
            'name' => $name,
            'email' => $email,
        ];

        if (!preg_match('/^[A-Za-zÀ-ÿ\' -]{2,100}$/u', $name)) {
            set_flash('error', 'Nome inválido.');
            $this->redirect('/card-leak-checker/public/register');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'E-mail inválido.');
            $this->redirect('/card-leak-checker/public/register');
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{12,}$/', $password)) {
            set_flash('error', 'A senha deve ter no mínimo 12 caracteres, com maiúscula, minúscula, número e símbolo.');
            $this->redirect('/card-leak-checker/public/register');
        }

        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            set_flash('error', 'E-mail já cadastrado.');
            $this->redirect('/card-leak-checker/public/register');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userModel->create($name, $email, $passwordHash);

        unset($_SESSION['old']);
        set_flash('success', 'Cadastro realizado com sucesso. Agora faça login.');
        $this->redirect('/card-leak-checker/public/login');
    }

    public function showLogin(): void
    {
        $this->view('auth/login');
    }

    public function login(): void
    {
        verify_csrf();

        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = client_ip();

        $loginAttemptModel = new LoginAttempt();
        $suspiciousModel = new SuspiciousEvent();

        $failedByIp = $loginAttemptModel->countRecentFailedByIp($ip, 15);
        $failedByEmail = $email !== '' ? $loginAttemptModel->countRecentFailedByEmail($email, 15) : 0;

        if ($failedByIp >= 5 || $failedByEmail >= 5) {
            $suspiciousModel->create(
                null,
                $email ?: null,
                $ip,
                'rate_limit_triggered',
                json_encode([
                    'failed_by_ip' => $failedByIp,
                    'failed_by_email' => $failedByEmail
                ], JSON_UNESCAPED_UNICODE)
            );

            set_flash('error', 'Muitas tentativas de login. Aguarde alguns minutos antes de tentar novamente.');
            $this->redirect('/card-leak-checker/public/login');
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $loginAttemptModel->create($email ?: null, $ip, false);

            $distinctEmails = $loginAttemptModel->countDistinctEmailsByIp($ip, 15);
            if ($distinctEmails >= 3) {
                $suspiciousModel->create(
                    null,
                    $email ?: null,
                    $ip,
                    'credential_stuffing_suspected',
                    json_encode([
                        'distinct_emails_tested' => $distinctEmails
                    ], JSON_UNESCAPED_UNICODE)
                );
            }

            set_flash('error', 'Credenciais inválidas.');
            $this->redirect('/card-leak-checker/public/login');
        }

        $loginAttemptModel->create($email, $ip, true);

        $_SESSION['pre_2fa_user_id'] = $user['id'];
        $_SESSION['pre_2fa_email'] = $user['email'];

        if ((int)$user['two_factor_enabled'] !== 1 || empty($user['two_factor_secret'])) {
            $this->redirect('/card-leak-checker/public/2fa/setup');
        }

        $this->redirect('/card-leak-checker/public/2fa/verify');
    }

    public function showSetup2FA(): void
    {
        if (empty($_SESSION['pre_2fa_user_id'])) {
            $this->redirect('/card-leak-checker/public/login');
        }

        $userModel = new User();
        $user = $userModel->findById((int)$_SESSION['pre_2fa_user_id']);

        if (!$user) {
            $this->redirect('/card-leak-checker/public/login');
        }

        $secret = generate_totp_secret();
        $_SESSION['temp_2fa_secret'] = $secret;

        $otpauth = generate_otpauth_uri($user['email'], $secret);

        $this->view('auth/setup-2fa', [
            'secret' => $secret,
            'otpauth' => $otpauth,
            'email' => $user['email'],
        ]);
    }

    public function setup2FA(): void
    {
        verify_csrf();

        if (empty($_SESSION['pre_2fa_user_id']) || empty($_SESSION['temp_2fa_secret'])) {
            $this->redirect('/card-leak-checker/public/login');
        }

        $code = trim($_POST['code'] ?? '');
        $secret = $_SESSION['temp_2fa_secret'];

        if (!verify_totp_code($secret, $code)) {
            set_flash('error', 'Código do Google Authenticator inválido.');
            $this->redirect('/card-leak-checker/public/2fa/setup');
        }

        $userId = (int)$_SESSION['pre_2fa_user_id'];
        $userModel = new User();
        $userModel->saveTwoFactorSecret($userId, $secret);

        $_SESSION['user_id'] = $userId;
        $_SESSION['two_factor_verified'] = true;

        unset($_SESSION['pre_2fa_user_id'], $_SESSION['pre_2fa_email'], $_SESSION['temp_2fa_secret']);

        session_regenerate_id(true);

        $this->redirect('/card-leak-checker/public/dashboard');
    }

    public function showVerify2FA(): void
    {
        if (empty($_SESSION['pre_2fa_user_id'])) {
            $this->redirect('/card-leak-checker/public/login');
        }

        $this->view('auth/verify-2fa');
    }

    public function verify2FA(): void
    {
        verify_csrf();

        if (empty($_SESSION['pre_2fa_user_id'])) {
            $this->redirect('/card-leak-checker/public/login');
        }

        $code = trim($_POST['code'] ?? '');
        $userId = (int)$_SESSION['pre_2fa_user_id'];

        $userModel = new User();
        $user = $userModel->findById($userId);

        if (!$user || empty($user['two_factor_secret'])) {
            set_flash('error', '2FA não configurado.');
            $this->redirect('/card-leak-checker/public/login');
        }

        if (!verify_totp_code($user['two_factor_secret'], $code)) {
            $suspiciousModel = new SuspiciousEvent();
            $suspiciousModel->create(
                $userId,
                $user['email'],
                client_ip(),
                'invalid_2fa_code',
                'Tentativa com código TOTP inválido'
            );

            set_flash('error', 'Código inválido.');
            $this->redirect('/card-leak-checker/public/2fa/verify');
        }

        $_SESSION['user_id'] = $userId;
        $_SESSION['two_factor_verified'] = true;

        unset($_SESSION['pre_2fa_user_id'], $_SESSION['pre_2fa_email']);

        session_regenerate_id(true);

        $this->redirect('/card-leak-checker/public/dashboard');
    }

    public function showForgotPassword(): void
    {
        $this->view('auth/forgot-password');
    }

    public function forgotPassword(): void
    {
        verify_csrf();

        $email = trim($_POST['email'] ?? '');
        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user) {
            $plainToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $plainToken);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $passwordResetModel = new PasswordReset();
            $passwordResetModel->invalidateAllByUser((int)$user['id']);
            $passwordResetModel->create((int)$user['id'], $user['email'], $tokenHash, $expiresAt);

            $appUrl = rtrim((string)env('APP_URL', 'http://localhost/card-leak-checker/public'), '/');
            $resetLink = $appUrl . '/reset-password?token=' . urlencode($plainToken);

            send_demo_mail(
                $user['email'],
                'Redefinição de senha',
                "Acesse o link para redefinir sua senha: {$resetLink}"
            );
        }

        set_flash('success', 'Se o e-mail existir, um link de redefinição foi gerado para a demonstração.');
        $this->redirect('/card-leak-checker/public/forgot-password');
    }

    public function showResetPassword(): void
    {
        $token = trim($_GET['token'] ?? '');

        if ($token === '') {
            set_flash('error', 'Token de redefinição inválido.');
            $this->redirect('/card-leak-checker/public/login');
        }

        $passwordResetModel = new PasswordReset();
        $reset = $passwordResetModel->findValidByToken($token);

        if (!$reset) {
            set_flash('error', 'Token inválido ou expirado.');
            $this->redirect('/card-leak-checker/public/login');
        }

        $this->view('auth/reset-password', [
            'token' => $token
        ]);
    }

    public function resetPassword(): void
    {
        verify_csrf();

        $token = trim($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirmation'] ?? '';

        if ($token === '') {
            set_flash('error', 'Token inválido.');
            $this->redirect('/card-leak-checker/public/login');
        }

        if ($password !== $passwordConfirm) {
            set_flash('error', 'As senhas não conferem.');
            $this->redirect('/card-leak-checker/public/reset-password?token=' . urlencode($token));
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{12,}$/', $password)) {
            set_flash('error', 'A nova senha deve ter no mínimo 12 caracteres, com maiúscula, minúscula, número e símbolo.');
            $this->redirect('/card-leak-checker/public/reset-password?token=' . urlencode($token));
        }

        $passwordResetModel = new PasswordReset();
        $reset = $passwordResetModel->findValidByToken($token);

        if (!$reset) {
            set_flash('error', 'Token inválido ou expirado.');
            $this->redirect('/card-leak-checker/public/login');
        }

        $userModel = new User();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userModel->updatePassword((int)$reset['user_id'], $passwordHash);

        $passwordResetModel->markAsUsed((int)$reset['id']);
        $passwordResetModel->invalidateAllByUser((int)$reset['user_id']);

        set_flash('success', 'Senha redefinida com sucesso. Faça login com a nova senha.');
        $this->redirect('/card-leak-checker/public/login');
    }

    public function logout(): void
    {
        verify_csrf();
        logout_user();
        $this->redirect('/card-leak-checker/public/login');
    }
}