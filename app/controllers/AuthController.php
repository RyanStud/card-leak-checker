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

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            set_flash('error', 'Credenciais inválidas.');
            $this->redirect('/card-leak-checker/public/login');
        }

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
            set_flash('error', 'Código inválido.');
            $this->redirect('/card-leak-checker/public/2fa/verify');
        }

        $_SESSION['user_id'] = $userId;
        $_SESSION['two_factor_verified'] = true;

        unset($_SESSION['pre_2fa_user_id'], $_SESSION['pre_2fa_email']);

        session_regenerate_id(true);

        $this->redirect('/card-leak-checker/public/dashboard');
    }

    public function logout(): void
    {
        verify_csrf();
        logout_user();
        $this->redirect('/card-leak-checker/public/login');
    }
}