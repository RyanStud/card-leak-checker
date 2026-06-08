<?php

class AuthController extends Controller
{
    public function showRegister(): void
    {
        $captcha = captcha_get_or_create('register');
        $this->view('auth/register', [
            'captchaImageUrl' => captcha_image_url('register'),
        ]);
    }

    public function register(): void
    {
        verify_csrf();

        // S.3.1 - Criptografia híbrida: se o cadastro chegou cifrado, decifra a
        // chave de sessão (RSA-OAEP) e, com ela, os dados do formulário
        // (AES-256-GCM), recolocando-os em $_POST para o fluxo normal seguir.
        if (($_POST['encrypted'] ?? '') === '1') {
            $decrypted = hybrid_crypto_decrypt(
                (string) ($_POST['enc_key'] ?? ''),
                (string) ($_POST['iv'] ?? ''),
                (string) ($_POST['payload'] ?? '')
            );

            if ($decrypted === null) {
                hybrid_crypto_console_log('Cadastro cifrado recebido, mas a decriptografia FALHOU.');
                set_flash('error', 'Não foi possível decifrar os dados enviados. Tente novamente.');
                $this->redirect(base_path('/register'));
            }

            // S.3.1.f - Mostra os dados decifrados no console do back.
            hybrid_crypto_console_log(sprintf(
                'Cadastro decifrado com sucesso [origem da chave: %s] | name=%s | email=%s | password=%s | captcha_answer=%s | lgpd_consent=%s',
                hybrid_crypto_material()['source'],
                (string) ($decrypted['name'] ?? ''),
                (string) ($decrypted['email'] ?? ''),
                (string) ($decrypted['password'] ?? ''),
                (string) ($decrypted['captcha_answer'] ?? ''),
                (string) ($decrypted['lgpd_consent'] ?? '')
            ));

            foreach (['name', 'email', 'password', 'captcha_answer', 'lgpd_consent'] as $field) {
                if (array_key_exists($field, $decrypted)) {
                    $_POST[$field] = $decrypted[$field];
                }
            }
        }

        $name = clean_text($_POST['name'] ?? '');
        $email = clean_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $lgpdConsent = $_POST['lgpd_consent'] ?? '';
        $captchaAnswer = clean_text($_POST['captcha_answer'] ?? '');

        $_SESSION['old'] = [
            'name' => $name,
            'email' => $email,
            'lgpd_consent' => $lgpdConsent,
        ];

        if (!preg_match('/^[A-Za-zÀ-ÿ\' -]{2,100}$/u', $name)) {
            set_flash('error', 'Nome inválido.');
            $this->redirect(base_path('/register'));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            set_flash('error', 'E-mail inválido.');
            $this->redirect(base_path('/register'));
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{12,}$/', $password)) {
            set_flash('error', 'A senha deve ter no mínimo 12 caracteres, com maiúscula, minúscula, número e símbolo.');
            $this->redirect(base_path('/register'));
        }

        if (!captcha_validate('register', $captchaAnswer)) {
            captcha_reset('register');
            set_flash('error', 'Captcha inválido. Tente novamente.');
            $this->redirect(base_path('/register'));
        }

        if ($lgpdConsent !== '1') {
            set_flash('error', 'Você precisa consentir com o tratamento de dados pessoais (LGPD) para se cadastrar.');
            $this->redirect(base_path('/register'));
        }

        $userModel = new User();
        if ($userModel->findByEmail($email)) {
            set_flash('error', 'E-mail já cadastrado.');
            $this->redirect(base_path('/register'));
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userModel->create($name, $email, $passwordHash);

        // S.3.2.c/d - prova do round-trip de criptografia do banco.
        $rawUser = $userModel->findRawByEmail($email);
        if ($rawUser !== null) {
            // S.3.2.c - como o dado sensível ficou persistido (cifrado) no BD.
            DbCipher::console('[S.3.2.c] Persistido CIFRADO no BD -> name=' . (string) ($rawUser['name'] ?? ''));
            // S.3.2.d - recuperado do BD e descriptografado na aplicação.
            DbCipher::console(sprintf(
                '[S.3.2.d] Recuperado do BD e descriptografado -> informações do cadastro: name=%s | email=%s',
                DbCipher::decrypt((string) ($rawUser['name'] ?? '')),
                (string) ($rawUser['email'] ?? '')
            ));
        }

        $createdUser = $userModel->findByEmail($email);

        if ($createdUser) {
            $plainToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $plainToken);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $verificationModel = new EmailVerification();
            $verificationModel->invalidateAllByUser((int)$createdUser['id']);
            $verificationModel->create((int)$createdUser['id'], $createdUser['email'], $tokenHash, $expiresAt);

            $appUrl = rtrim((string)env('APP_URL', 'http://localhost/card-leak-checker'), '/');
            $verificationLink = $appUrl . '/confirm-email?token=' . urlencode($plainToken);

            send_demo_mail(
                $createdUser['email'],
                'Confirmação de cadastro',
                "Confirme seu cadastro acessando o link: {$verificationLink}"
            );
        }

        unset($_SESSION['old']);
        set_flash('success', 'Cadastro realizado. Enviamos um link de confirmação para o seu e-mail.');
        $this->redirect(base_path('/register/confirmation'));
    }

    public function showLogin(): void
    {
        $captcha = captcha_get_or_create('login');
        $adminCaptcha = captcha_get_or_create('admin_passwordless');

        $this->view('auth/login', [
            'captchaImageUrl' => captcha_image_url('login'),
            'adminCaptchaImageUrl' => captcha_image_url('admin_passwordless'),
        ]);
    }

    public function showRegisterConfirmation(): void
    {
        $this->view('auth/register-confirmation');
    }

    public function confirmEmail(): void
    {
        $token = clean_text($_GET['token'] ?? '');

        if ($token === '') {
            set_flash('error', 'Token de confirmação inválido.');
            $this->redirect(base_path('/register/confirmation'));
        }

        $verificationModel = new EmailVerification();
        $verification = $verificationModel->findValidByToken($token);

        if (!$verification) {
            set_flash('error', 'Link de confirmação inválido ou expirado.');
            $this->redirect(base_path('/register/confirmation'));
        }

        $userModel = new User();
        $userModel->markEmailAsVerified((int)$verification['user_id']);

        $verificationModel->markAsUsed((int)$verification['id']);
        $verificationModel->invalidateAllByUser((int)$verification['user_id']);

        set_flash('success', 'E-mail confirmado com sucesso. Agora você pode fazer login.');
        $this->redirect(base_path('/login'));
    }

    public function login(): void
    {
        verify_csrf();

        $email = clean_email($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $captchaAnswer = clean_text($_POST['captcha_answer'] ?? '');
        $ip = client_ip();

        if (!captcha_validate('login', $captchaAnswer)) {
            captcha_reset('login');
            set_flash('error', 'Captcha inválido. Tente novamente.');
            $this->redirect(base_path('/login'));
        }

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
            $this->redirect(base_path('/login'));
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
            $this->redirect(base_path('/login'));
        }

        if ((int)$user['email_verified'] !== 1) {
            set_flash('error', 'Confirme seu e-mail antes de fazer login. Verifique o link enviado no cadastro.');
            $this->redirect(base_path('/register/confirmation'));
        }

        $loginAttemptModel->create($email, $ip, true);

        $_SESSION['pre_2fa_user_id'] = $user['id'];
        $_SESSION['pre_2fa_email'] = $user['email'];

        if ((int)$user['two_factor_enabled'] !== 1 || empty($user['two_factor_secret'])) {
            $this->redirect(base_path('/2fa/setup'));
        }

        $this->redirect(base_path('/2fa/verify'));
    }

    public function showAdminPasswordless(): void
    {
        $captcha = captcha_get_or_create('admin_passwordless');
        $canUseQuestions = false;
        $codeSentAt = null;

        $userId = (int)($_SESSION['admin_passwordless_user_id'] ?? 0);
        if ($userId > 0) {
            $securityModel = new UserSecurityAnswer();
            $answered = $securityModel->countUserAnswers($userId);
            if ($answered >= 5) {
                $canUseQuestions = true;
            }

            $codeSentAt = (int)($_SESSION['admin_passwordless_code_sent_at'] ?? 0);
            // if not in session, try to get from telegram_accounts table
            if ($codeSentAt <= 0) {
                $tm = new TelegramAccount();
                $ta = $tm->findByUserId($userId);
                if (!empty($ta['login_code_sent_at'])) {
                    $ts = strtotime($ta['login_code_sent_at']);
                    if ($ts !== false) {
                        $codeSentAt = (int)$ts;
                    }
                }
            }
        }

        $this->view('auth/admin-passwordless', [
            'captchaImageUrl' => captcha_image_url('admin_passwordless'),
            'canUseQuestions' => $canUseQuestions,
            'codeSentAt' => $codeSentAt,
        ]);
    }

    public function requestAdminPasswordless(): void
    {
        verify_csrf();

        $email = clean_email($_POST['email'] ?? '');
        $captchaAnswer = clean_text($_POST['captcha_answer'] ?? '');

        if (!captcha_validate('admin_passwordless', $captchaAnswer)) {
            captcha_reset('admin_passwordless');
            set_flash('error', 'Captcha inválido.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || ($user['role'] ?? 'user') !== 'admin' || (int)($user['email_verified'] ?? 0) !== 1) {
            set_flash('success', 'Se o e-mail informado estiver habilitado, um código será enviado em instantes.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        $mode = strtolower(trim((string)env('TELEGRAM_MODE', 'api')));
        $userId = (int)$user['id'];

        if ($mode === 'log') {
            $lastSentAt = (int)($_SESSION['admin_passwordless_code_sent_at'] ?? 0);
            if ($lastSentAt > 0 && (time() - $lastSentAt) < 30) {
                set_flash('error', 'Aguarde 30 segundos para solicitar novo código.');
                $this->redirect(base_path('/admin/passwordless'));
            }

            $code = (string)random_int(100000, 999999);
            $_SESSION['admin_passwordless_user_id'] = $userId;
            $_SESSION['admin_passwordless_code_hash'] = hash('sha256', $code);
            $_SESSION['admin_passwordless_code_expires'] = time() + 300;
            $_SESSION['admin_passwordless_code_sent_at'] = time();

            app_log('admin_passwordless_log_mode user_id=' . (string)$userId . ' code=' . $code . ' expires_in=300s');

            set_flash('success', 'Código gerado em modo local. Verifique storage/logs/app.log.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        $telegramModel = new TelegramAccount();
        $telegramAccount = $telegramModel->findByUserId($userId);

        if (!$telegramAccount || empty($telegramAccount['telegram_user_id']) || empty($telegramAccount['is_active'])) {
            set_flash('success', 'Se o e-mail informado estiver habilitado, um código será enviado em instantes.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        $lastSentRaw = trim((string)($telegramAccount['login_code_sent_at'] ?? ''));
        if ($lastSentRaw !== '') {
            $lastSentTs = strtotime($lastSentRaw);
            if ($lastSentTs !== false && (time() - $lastSentTs) < 30) {
                set_flash('error', 'Aguarde 30 segundos para solicitar novo código.');
                $this->redirect(base_path('/admin/passwordless'));
            }
        }

        $code = (string)random_int(100000, 999999);
        $saved = $telegramModel->issueLoginCode($userId, $code, date('Y-m-d H:i:s', time() + 300));

        if (!$saved) {
            set_flash('error', 'Não foi possível preparar o código de acesso.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        $sent = telegram_send_message(
            (int)$telegramAccount['telegram_user_id'],
            'Seu código de acesso admin passwordless: ' . $code . ' (válido por 5 minutos).'
        );

        if (!$sent) {
            set_flash('error', 'Falha ao enviar código no Telegram.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        $_SESSION['admin_passwordless_user_id'] = $userId;
        $_SESSION['pre_2fa_user_id'] = $userId;
        $_SESSION['pre_2fa_email'] = $user['email'];
        // mark when the code was sent so client can enable fallback after 60s
        $_SESSION['admin_passwordless_code_sent_at'] = time();
        set_flash('success', 'Código enviado no Telegram.');
        $this->redirect(base_path('/admin/passwordless'));
    }

    public function verifyAdminPasswordless(): void
    {
        verify_csrf();

        $code = clean_numeric_text($_POST['code'] ?? '');
        $userId = (int)($_SESSION['admin_passwordless_user_id'] ?? 0);

        if ($userId <= 0 || preg_match('/^\d{6}$/', $code) !== 1) {
            set_flash('error', 'Código inválido.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        $userModel = new User();
        $user = $userModel->findById($userId);
        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            set_flash('error', 'Administrador inválido para acesso passwordless.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        $mode = strtolower(trim((string)env('TELEGRAM_MODE', 'api')));
        if ($mode === 'log') {
            $expectedHash = (string)($_SESSION['admin_passwordless_code_hash'] ?? '');
            $expiresAt = (int)($_SESSION['admin_passwordless_code_expires'] ?? 0);

            $ok = ($expectedHash !== '')
                && $expiresAt >= time()
                && hash_equals($expectedHash, hash('sha256', $code));

            if ($ok) {
                unset(
                    $_SESSION['admin_passwordless_code_hash'],
                    $_SESSION['admin_passwordless_code_expires'],
                    $_SESSION['admin_passwordless_code_sent_at']
                );
            }
        } else {
            $telegramModel = new TelegramAccount();
            $ok = $telegramModel->consumeValidLoginCode($userId, $code);
        }

        if (!$ok) {
            $suspiciousModel = new SuspiciousEvent();
            $suspiciousModel->create(
                $userId,
                $user['email'],
                client_ip(),
                'admin_passwordless_code_invalid',
                'Tentativa com código inválido no login passwordless admin'
            );

            set_flash('error', 'Código incorreto ou expirado.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        $_SESSION['pre_2fa_user_id'] = $userId;
        $_SESSION['pre_2fa_email'] = $user['email'];
        $_SESSION['admin_passwordless_user_id'] = $userId;

        if ((int)$user['two_factor_enabled'] !== 1 || empty($user['two_factor_secret'])) {
            $this->redirect(base_path('/2fa/setup'));
        }

        $this->redirect(base_path('/2fa/verify'));
    }

    public function showAdminPasswordlessQuestions(): void
    {
        $userId = (int)($_SESSION['admin_passwordless_user_id'] ?? 0);
        if ($userId <= 0) {
            $this->redirect(base_path('/admin/passwordless'));
        }

        $securityModel = new UserSecurityAnswer();
        $indices = $securityModel->getUserQuestionIndices($userId);
        if (count($indices) < 5) {
            set_flash('error', 'Você não tem perguntas de segurança suficientes cadastradas.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        // ensure at least 60s passed since code was sent
        $sentAt = (int)($_SESSION['admin_passwordless_code_sent_at'] ?? 0);
        if ($sentAt <= 0) {
            $tm = new TelegramAccount();
            $ta = $tm->findByUserId($userId);
            if (!empty($ta['login_code_sent_at'])) {
                $ts = strtotime($ta['login_code_sent_at']);
                if ($ts !== false) {
                    $sentAt = (int)$ts;
                }
            }
        }

        if ($sentAt > 0 && (time() - $sentAt) < 60) {
            set_flash('error', 'Aguarde 1 minuto para usar as perguntas de segurança como fallback.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        // pick 3 random
        shuffle($indices);
        $pick = array_slice($indices, 0, 3);

        require_once __DIR__ . '/../helpers/security_questions.php';
        $questions = security_questions_list();

        $selected = [];
        foreach ($pick as $i) {
            $selected[$i] = $questions[$i] ?? 'Pergunta não encontrada';
        }

        $this->view('auth/admin-passwordless-questions', [
            'questions' => $selected,
        ]);
    }

    public function verifyAdminPasswordlessQuestions(): void
    {
        verify_csrf();
        $userId = (int)($_SESSION['admin_passwordless_user_id'] ?? 0);
        if ($userId <= 0) {
            set_flash('error', 'Sessão inválida para verificação.');
            $this->redirect(base_path('/admin/passwordless'));
        }

        $provided = [];
        foreach ($_POST as $k => $v) {
            if (str_starts_with($k, 'q_')) {
                $idx = (int)substr($k, 2);
                $provided[$idx] = trim((string)$v);
            }
        }

        if (count($provided) !== 3) {
            set_flash('error', 'Forneça as 3 respostas solicitadas.');
            $this->redirect(base_path('/admin/passwordless/questions'));
        }

        $securityModel = new UserSecurityAnswer();
        $ok = $securityModel->verifyAnswers($userId, $provided);

        if (!$ok) {
            $suspiciousModel = new SuspiciousEvent();
            $suspiciousModel->create(
                $userId,
                null,
                client_ip(),
                'admin_security_questions_failed',
                'Falha na verificação por perguntas de segurança para acesso admin'
            );

            set_flash('error', 'Respostas incorretas.');
            $this->redirect(base_path('/admin/passwordless/questions'));
        }

        $_SESSION['pre_2fa_user_id'] = $userId;
        $userModel = new User();
        $user = $userModel->findById($userId);
        $_SESSION['pre_2fa_email'] = $user['email'] ?? '';

        if ((int)$user['two_factor_enabled'] !== 1 || empty($user['two_factor_secret'])) {
            $this->redirect(base_path('/2fa/setup'));
        }

        $this->redirect(base_path('/2fa/verify'));
    }

    public function showSetup2FA(): void
    {
        if (empty($_SESSION['pre_2fa_user_id'])) {
            $this->redirect(base_path('/login'));
        }

        $userModel = new User();
        $user = $userModel->findById((int)$_SESSION['pre_2fa_user_id']);

        if (!$user) {
            $this->redirect(base_path('/login'));
        }

        $secret = generate_totp_secret();
        $_SESSION['temp_2fa_secret'] = $secret;

        $otpauth = generate_otpauth_uri($user['email'], $secret);
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . rawurlencode($otpauth);

        $this->view('auth/setup-2fa', [
            'secret' => $secret,
            'otpauth' => $otpauth,
            'qrCodeUrl' => $qrCodeUrl,
            'email' => $user['email'],
        ]);
    }

    public function setup2FA(): void
    {
        verify_csrf();

        if (empty($_SESSION['pre_2fa_user_id']) || empty($_SESSION['temp_2fa_secret'])) {
            $this->redirect(base_path('/login'));
        }

        $code = clean_numeric_text($_POST['code'] ?? '');
        $secret = $_SESSION['temp_2fa_secret'];

        if (!verify_totp_code($secret, $code)) {
            set_flash('error', 'Código do Google Authenticator inválido.');
            $this->redirect(base_path('/2fa/setup'));
        }

        $userId = (int)$_SESSION['pre_2fa_user_id'];
        $userModel = new User();
        $userModel->saveTwoFactorSecret($userId, $secret);
        $user = $userModel->findById($userId);

        $_SESSION['user_id'] = $userId;
        $_SESSION['two_factor_verified'] = true;
        $_SESSION['admin_access_mode'] = !empty($_SESSION['admin_passwordless_user_id']) ? 'privileged' : ((($user['role'] ?? 'user') === 'admin') ? 'restricted' : 'none');
        $_SESSION['admin_elevated_until'] = 0;

        if (!empty($_SESSION['admin_passwordless_user_id'])) {
            $ttl = (int)env('ADMIN_ELEVATION_TTL', 900);
            if ($ttl < 60) {
                $ttl = 900;
            }

            $_SESSION['admin_elevated_until'] = time() + $ttl;

            $audit = new AuditLog();
            $audit->create(
                $userId,
                null,
                'admin_passwordless_login',
                json_encode([
                    'mode' => 'telegram_then_mfa_setup',
                    'elevated_until' => date('Y-m-d H:i:s', (int)$_SESSION['admin_elevated_until']),
                ], JSON_UNESCAPED_UNICODE)
            );

            unset($_SESSION['pre_2fa_user_id'], $_SESSION['pre_2fa_email'], $_SESSION['temp_2fa_secret']);
            unset($_SESSION['admin_passwordless_user_id']);

            session_regenerate_id(true);
            bind_authenticated_session_context();
            set_flash('success', 'Acesso admin passwordless concluído com sucesso.');
            $this->redirect(base_path('/admin'));
        }

        unset($_SESSION['pre_2fa_user_id'], $_SESSION['pre_2fa_email'], $_SESSION['temp_2fa_secret']);

        session_regenerate_id(true);
        bind_authenticated_session_context();

        $this->redirect(base_path('/dashboard'));
    }

    public function showVerify2FA(): void
    {
        if (empty($_SESSION['pre_2fa_user_id'])) {
            $this->redirect(base_path('/login'));
        }

        $this->view('auth/verify-2fa');
    }

    public function verify2FA(): void
    {
        verify_csrf();

        if (empty($_SESSION['pre_2fa_user_id'])) {
            $this->redirect(base_path('/login'));
        }

        $code = clean_numeric_text($_POST['code'] ?? '');
        $userId = (int)$_SESSION['pre_2fa_user_id'];

        $userModel = new User();
        $user = $userModel->findById($userId);

        if (!$user || empty($user['two_factor_secret'])) {
            set_flash('error', '2FA não configurado.');
            $this->redirect(base_path('/login'));
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
            $this->redirect(base_path('/2fa/verify'));
        }

        $_SESSION['user_id'] = $userId;
        $_SESSION['two_factor_verified'] = true;
        $_SESSION['admin_access_mode'] = !empty($_SESSION['admin_passwordless_user_id']) ? 'privileged' : ((($user['role'] ?? 'user') === 'admin') ? 'restricted' : 'none');
        $_SESSION['admin_elevated_until'] = 0;

        if (!empty($_SESSION['admin_passwordless_user_id'])) {
            $ttl = (int)env('ADMIN_ELEVATION_TTL', 900);
            if ($ttl < 60) {
                $ttl = 900;
            }

            $_SESSION['admin_elevated_until'] = time() + $ttl;

            $audit = new AuditLog();
            $audit->create(
                $userId,
                null,
                'admin_passwordless_login',
                json_encode([
                    'mode' => 'telegram_then_mfa',
                    'elevated_until' => date('Y-m-d H:i:s', (int)$_SESSION['admin_elevated_until']),
                ], JSON_UNESCAPED_UNICODE)
            );

            unset($_SESSION['pre_2fa_user_id'], $_SESSION['pre_2fa_email']);
            unset($_SESSION['admin_passwordless_user_id']);

            session_regenerate_id(true);
            bind_authenticated_session_context();
            set_flash('success', 'Acesso admin passwordless concluído com sucesso.');
            $this->redirect(base_path('/admin'));
        }

        unset($_SESSION['pre_2fa_user_id'], $_SESSION['pre_2fa_email']);

        session_regenerate_id(true);
        bind_authenticated_session_context();

        $this->redirect(base_path('/dashboard'));
    }

    public function showForgotPassword(): void
    {
        $captcha = captcha_get_or_create('forgot_password');
        $this->view('auth/forgot-password', [
            'captchaImageUrl' => captcha_image_url('forgot_password'),
        ]);
    }

    public function forgotPassword(): void
    {
        verify_csrf();

        $email = clean_email($_POST['email'] ?? '');
        $captchaAnswer = clean_text($_POST['captcha_answer'] ?? '');

        if (!captcha_validate('forgot_password', $captchaAnswer)) {
            captcha_reset('forgot_password');
            set_flash('error', 'Captcha inválido.');
            $this->redirect(base_path('/forgot-password'));
        }

        $ip = client_ip();
        $suspiciousModel = new SuspiciousEvent();

        $recentResetRequests = $suspiciousModel->countRecentByIpAndType($ip, 'password_reset_request', 15);

        $suspiciousModel->create(
            null,
            $email !== '' ? $email : null,
            $ip,
            'password_reset_request',
            json_encode([
                'window_minutes' => 15,
                'recent_requests_before_current' => $recentResetRequests,
            ], JSON_UNESCAPED_UNICODE)
        );

        if ($recentResetRequests >= 5) {
            $suspiciousModel->create(
                null,
                $email !== '' ? $email : null,
                $ip,
                'password_reset_abuse',
                json_encode([
                    'window_minutes' => 15,
                    'recent_requests_before_current' => $recentResetRequests,
                ], JSON_UNESCAPED_UNICODE)
            );
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if ($user) {
            $plainToken = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $plainToken);
            $expiresAt = date('Y-m-d H:i:s', time() + 3600);

            $passwordResetModel = new PasswordReset();
            $passwordResetModel->invalidateAllByUser((int)$user['id']);
            $passwordResetModel->create((int)$user['id'], $user['email'], $tokenHash, $expiresAt);

            $appUrl = rtrim((string)env('APP_URL', 'http://localhost/card-leak-checker'), '/');
            $resetLink = $appUrl . '/reset-password?token=' . urlencode($plainToken);

            send_demo_mail(
                $user['email'],
                'Redefinição de senha',
                "Acesse o link para redefinir sua senha: {$resetLink}"
            );
        }

        set_flash('success', 'Se o e-mail existir, um link de redefinição foi gerado para a demonstração.');
        $this->redirect(base_path('/forgot-password'));
    }

    public function showResetPassword(): void
    {
        $token = clean_text($_GET['token'] ?? '');

        if ($token === '') {
            set_flash('error', 'Token de redefinição inválido.');
            $this->redirect(base_path('/login'));
        }

        $passwordResetModel = new PasswordReset();
        $reset = $passwordResetModel->findValidByToken($token);

        if (!$reset) {
            set_flash('error', 'Token inválido ou expirado.');
            $this->redirect(base_path('/login'));
        }

        $captcha = captcha_get_or_create('reset_password');
        $this->view('auth/reset-password', [
            'token' => $token,
            'captchaImageUrl' => captcha_image_url('reset_password'),
        ]);
    }

    public function resetPassword(): void
    {
        verify_csrf();

        $token = clean_text($_POST['token'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirmation'] ?? '';
        $captchaAnswer = clean_text($_POST['captcha_answer'] ?? '');

        if (!captcha_validate('reset_password', $captchaAnswer)) {
            captcha_reset('reset_password');
            set_flash('error', 'Captcha inválido.');
            $this->redirect(base_path('/reset-password') . '?token=' . urlencode($token));
        }

        if ($token === '') {
            set_flash('error', 'Token inválido.');
            $this->redirect(base_path('/login'));
        }

        if ($password !== $passwordConfirm) {
            set_flash('error', 'As senhas não conferem.');
            $this->redirect(base_path('/reset-password') . '?token=' . urlencode($token));
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{12,}$/', $password)) {
            set_flash('error', 'A nova senha deve ter no mínimo 12 caracteres, com maiúscula, minúscula, número e símbolo.');
            $this->redirect(base_path('/reset-password') . '?token=' . urlencode($token));
        }

        $passwordResetModel = new PasswordReset();
        $reset = $passwordResetModel->findValidByToken($token);

        if (!$reset) {
            set_flash('error', 'Token inválido ou expirado.');
            $this->redirect(base_path('/login'));
        }

        $userModel = new User();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userModel->updatePassword((int)$reset['user_id'], $passwordHash);

        $passwordResetModel->markAsUsed((int)$reset['id']);
        $passwordResetModel->invalidateAllByUser((int)$reset['user_id']);

        set_flash('success', 'Senha redefinida com sucesso. Faça login com a nova senha.');
        $this->redirect(base_path('/login'));
    }

    public function logout(): void
    {
        // Logout is a safe action; if CSRF token expired/missing, still end session.
        $token = $_POST['_csrf'] ?? '';
        $csrfOk = !empty($_SESSION['csrf_token'])
            && is_string($token)
            && hash_equals((string)$_SESSION['csrf_token'], $token);

        if (!$csrfOk) {
            $suspiciousModel = new SuspiciousEvent();
            $suspiciousModel->create(
                isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null,
                null,
                client_ip(),
                'logout_csrf_mismatch',
                'Logout recebido com CSRF ausente ou inválido'
            );
        }

        logout_user();
        $this->redirect(base_path('/login'));
    }

    public function captchaImage(): void
    {
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Expires: 0');
        
        $context = clean_text($_GET['context'] ?? 'default');
        $key = 'captcha_' . $context;
        
        if (!isset($_SESSION[$key])) {
            $captcha = captcha_get_or_create($context);
        } else {
            $captcha = $_SESSION[$key];
        }
        
        try {
            echo captcha_generate_image($captcha['answer'] ?? '');
        } catch (Exception $e) {
            error_log('CAPTCHA generation error: ' . $e->getMessage());
            http_response_code(500);
            echo 'Error generating CAPTCHA';
        }
        exit;
    }
}
