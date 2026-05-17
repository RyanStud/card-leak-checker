<?php

if (!class_exists('TelegramAccount')) {
    require_once __DIR__ . '/../models/TelegramAccount.php';
}

if (!function_exists('telegram_send_message')) {
    require_once __DIR__ . '/../helpers/telegram.php';
}

class TelegramController extends Controller
{
    public function webhook(): void
    {
        $expectedSecret = trim((string)secret('TELEGRAM_WEBHOOK_SECRET', ''));
        if ($expectedSecret !== '') {
            $receivedSecret = trim((string)($_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? ''));
            if (!hash_equals($expectedSecret, $receivedSecret)) {
                http_response_code(403);
                echo 'forbidden';
                return;
            }
        }

        $payload = file_get_contents('php://input');
        if ($payload === false || trim($payload) === '') {
            http_response_code(400);
            echo 'invalid_payload';
            return;
        }

        $update = json_decode($payload, true);
        if (!is_array($update)) {
            http_response_code(400);
            echo 'invalid_json';
            return;
        }

        $this->processUpdate($update);

        http_response_code(200);
        echo 'ok';
    }

    public function processUpdate(array $update): void
    {
        $message = $update['message'] ?? null;
        if (!is_array($message)) {
            return;
        }

        $from = $message['from'] ?? [];
        $chat = $message['chat'] ?? [];
        $telegramUserId = (int)($from['id'] ?? 0);
        $chatId = (int)($chat['id'] ?? 0);

        if ($telegramUserId <= 0 || $chatId === 0) {
            return;
        }

        $telegramModel = new TelegramAccount();
        $text = trim((string)($message['text'] ?? ''));

        if (preg_match('/^\/start\s+link_([a-f0-9]{32})$/i', $text, $matches) === 1) {
            $plainCode = strtolower($matches[1]);
            $pending = $telegramModel->findPendingByCode($plainCode);

            if (!$pending) {
                $this->sendTelegramMessage($chatId, 'Codigo de vinculacao invalido ou expirado. Gere um novo link no dashboard.');
                return;
            }

            $existingByTelegram = $telegramModel->findByTelegramUserId($telegramUserId);
            if ($existingByTelegram && (int)$existingByTelegram['user_id'] !== (int)$pending['user_id']) {
                $this->sendTelegramMessage($chatId, 'Este Telegram ja esta vinculado a outra conta.');
                return;
            }

            $username = trim((string)($from['username'] ?? ''));
            $firstName = trim((string)($from['first_name'] ?? ''));
            $lastName = trim((string)($from['last_name'] ?? ''));

            $telegramModel->completeLink(
                (int)$pending['id'],
                $telegramUserId,
                $username !== '' ? $username : null,
                $firstName !== '' ? $firstName : null,
                $lastName !== '' ? $lastName : null,
                null
            );

            $this->sendTelegramMessage($chatId, 'Conta vinculada com sucesso. Agora voce pode receber verificacoes e alertas por aqui.');
            return;
        }

        if ($text === '/start') {
            $this->sendTelegramMessage(
                $chatId,
                'Ola. Para vincular sua conta, gere o link no dashboard e clique nele. Se precisar, envie seu contato usando o botao de compartilhar contato.'
            );
            return;
        }

        if (isset($message['contact']) && is_array($message['contact'])) {
            $contact = $message['contact'];
            $contactUserId = (int)($contact['user_id'] ?? 0);
            $contactPhone = trim((string)($contact['phone_number'] ?? ''));
            $digits = preg_replace('/\D+/', '', $contactPhone);

            if ($contactUserId === $telegramUserId && is_string($digits) && $digits !== '') {
                $telegramModel->savePhoneByTelegramUserId($telegramUserId, '+' . $digits);
                $this->sendTelegramMessage($chatId, 'Telefone atualizado com sucesso.');
                return;
            }
        }

        $telegramModel->touchInteractionByTelegramUserId($telegramUserId);
    }

    private function sendTelegramMessage(int $chatId, string $text): void
    {
        telegram_send_message($chatId, $text);
    }
}
