<?php

if (!class_exists('TelegramAccount')) {
    require_once __DIR__ . '/../models/TelegramAccount.php';
}

class DashboardController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::handle();

        $userModel = new User();
        $user = $userModel->findById((int)$_SESSION['user_id']);
        $telegramModel = new TelegramAccount();
        $telegramAccount = $telegramModel->findByUserId((int)$_SESSION['user_id']);

        $telegramBotUsername = trim((string)env('TELEGRAM_BOT_USERNAME', ''));
        $telegramPendingLinkUrl = (string)($_SESSION['telegram_pending_link_url'] ?? '');
        unset($_SESSION['telegram_pending_link_url']);

        $this->view('dashboard/index', [
            'user' => $user,
            'telegramAccount' => $telegramAccount,
            'telegramBotUsername' => $telegramBotUsername,
            'telegramPendingLinkUrl' => $telegramPendingLinkUrl,
        ]);
    }

    public function requestTelegramLink(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $botToken = trim((string)secret('APP_KEY_TELEGRAM', ''));
        if ($botToken === '') {
            set_flash('error', 'Bot Telegram nao configurado. Defina APP_KEY_TELEGRAM nos segredos.');
            $this->redirect(base_path('/dashboard'));
        }

        $botUsername = trim((string)env('TELEGRAM_BOT_USERNAME', ''));
        if ($botUsername === '') {
            set_flash('error', 'Defina TELEGRAM_BOT_USERNAME no .env para gerar o link de vinculacao.');
            $this->redirect(base_path('/dashboard'));
        }

        if (!preg_match('/^[A-Za-z0-9_]{5,32}$/', $botUsername)) {
            set_flash('error', 'TELEGRAM_BOT_USERNAME invalido.');
            $this->redirect(base_path('/dashboard'));
        }

        $plainCode = bin2hex(random_bytes(16));
        $telegramModel = new TelegramAccount();
        $telegramModel->createOrRefreshPendingLink(
            (int)$_SESSION['user_id'],
            $plainCode,
            date('Y-m-d H:i:s', time() + (15 * 60))
        );

        $_SESSION['telegram_pending_link_url'] = 'https://t.me/' . $botUsername . '?start=link_' . $plainCode;

        set_flash('success', 'Link de vinculacao Telegram gerado. Validade: 15 minutos.');
        $this->redirect(base_path('/dashboard'));
    }

    public function saveTelegramPreferences(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $usernameRaw = trim((string)($_POST['telegram_username'] ?? ''));
        $phoneDigits = clean_numeric_text($_POST['telegram_phone'] ?? '');
        $notificationsEnabled = !empty($_POST['notifications_enabled']) ? 1 : 0;

        if ($usernameRaw !== '' && preg_match('/^@?[A-Za-z0-9_]{5,32}$/', $usernameRaw) !== 1) {
            set_flash('error', 'Username do Telegram invalido. Use de 5 a 32 caracteres (letras, numeros e underscore).');
            $this->redirect(base_path('/dashboard'));
        }

        if ($phoneDigits !== '' && (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 15)) {
            set_flash('error', 'Telefone do Telegram invalido. Use DDI + DDD + numero (10 a 15 digitos).');
            $this->redirect(base_path('/dashboard'));
        }

        $normalizedUsername = ltrim($usernameRaw, '@');
        $normalizedPhone = $phoneDigits === '' ? null : '+' . $phoneDigits;

        $telegramModel = new TelegramAccount();
        $telegramModel->saveUserPreferences(
            (int)$_SESSION['user_id'],
            $normalizedUsername !== '' ? $normalizedUsername : null,
            $normalizedPhone,
            $notificationsEnabled
        );

        set_flash('success', 'Preferencias do Telegram salvas com sucesso.');
        $this->redirect(base_path('/dashboard'));
    }

    public function unlinkTelegram(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $telegramModel = new TelegramAccount();
        $telegramModel->unlinkByUserId((int)$_SESSION['user_id']);

        set_flash('success', 'Conta do Telegram desvinculada com sucesso.');
        $this->redirect(base_path('/dashboard'));
    }

    public function updateProfile(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $userId = (int)$_SESSION['user_id'];
        $userModel = new User();

        $name = clean_text($_POST['name'] ?? '');
        $cpf = clean_numeric_text($_POST['cpf'] ?? '');
        $jobTitle = clean_text($_POST['job_title'] ?? '');
        $cep = clean_numeric_text($_POST['cep'] ?? '');
        $addressStreet = clean_text($_POST['address_street'] ?? '');
        $addressNumber = clean_text($_POST['address_number'] ?? '');
        $addressComplement = clean_text($_POST['address_complement'] ?? '');
        $addressNeighborhood = clean_text($_POST['address_neighborhood'] ?? '');
        $addressCity = clean_text($_POST['address_city'] ?? '');
        $addressState = strtoupper(clean_text($_POST['address_state'] ?? ''));

        if (!preg_match('/^[A-Za-zÀ-ÿ\' -]{2,100}$/u', $name)) {
            set_flash('error', 'Nome invalido.');
            $this->redirect(base_path('/dashboard'));
        }

        if ($cpf !== '' && !self::isValidCpf($cpf)) {
            set_flash('error', 'CPF invalido.');
            $this->redirect(base_path('/dashboard'));
        }

        if ($cpf !== '') {
            $cpfOwner = $userModel->findByCpf($cpf);
            if ($cpfOwner && (int)$cpfOwner['id'] !== $userId) {
                set_flash('error', 'CPF ja cadastrado para outro usuario.');
                $this->redirect(base_path('/dashboard'));
            }
        }

        if ($cep !== '' && strlen($cep) !== 8) {
            set_flash('error', 'CEP invalido. Use 8 digitos.');
            $this->redirect(base_path('/dashboard'));
        }

        if ($addressState !== '' && preg_match('/^[A-Z]{2}$/', $addressState) !== 1) {
            set_flash('error', 'UF invalida. Use 2 letras.');
            $this->redirect(base_path('/dashboard'));
        }

        $saved = $userModel->updateProfile(
            $userId,
            $name,
            $cpf !== '' ? $cpf : null,
            $jobTitle !== '' ? $jobTitle : null,
            $cep !== '' ? $cep : null,
            $addressStreet !== '' ? $addressStreet : null,
            $addressNumber !== '' ? $addressNumber : null,
            $addressComplement !== '' ? $addressComplement : null,
            $addressNeighborhood !== '' ? $addressNeighborhood : null,
            $addressCity !== '' ? $addressCity : null,
            $addressState !== '' ? $addressState : null
        );

        if (!$saved) {
            set_flash('error', 'Nao foi possivel atualizar o perfil.');
            $this->redirect(base_path('/dashboard'));
        }

        set_flash('success', 'Perfil atualizado com sucesso.');
        $this->redirect(base_path('/dashboard'));
    }

    public function updatePassword(): void
    {
        AuthMiddleware::handle();
        verify_csrf();

        $userId = (int)$_SESSION['user_id'];
        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $newPasswordConfirmation = (string)($_POST['new_password_confirmation'] ?? '');

        if ($newPassword !== $newPasswordConfirmation) {
            set_flash('error', 'A confirmacao da nova senha nao confere.');
            $this->redirect(base_path('/dashboard'));
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z\d]).{12,}$/', $newPassword)) {
            set_flash('error', 'A nova senha deve ter no minimo 12 caracteres, com maiuscula, minuscula, numero e simbolo.');
            $this->redirect(base_path('/dashboard'));
        }

        $userModel = new User();
        $user = $userModel->findById($userId);

        if (!$user || !password_verify($currentPassword, (string)$user['password_hash'])) {
            set_flash('error', 'Senha atual incorreta.');
            $this->redirect(base_path('/dashboard'));
        }

        if ($userModel->hasRecentlyUsedPassword($userId, $newPassword)) {
            set_flash('error', 'A nova senha nao pode ser igual a senha atual nem uma das ultimas senhas usadas.');
            $this->redirect(base_path('/dashboard'));
        }

        $saved = $userModel->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT));

        if (!$saved) {
            set_flash('error', 'Nao foi possivel alterar a senha.');
            $this->redirect(base_path('/dashboard'));
        }

        set_flash('success', 'Senha alterada com sucesso.');
        $this->redirect(base_path('/dashboard'));
    }

    private static function isValidCpf(string $cpf): bool
    {
        if (preg_match('/^\d{11}$/', $cpf) !== 1) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($c = 0; $c < $t; $c++) {
                $sum += ((int)$cpf[$c]) * (($t + 1) - $c);
            }

            $digit = ((10 * $sum) % 11) % 10;
            if ((int)$cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }
}
