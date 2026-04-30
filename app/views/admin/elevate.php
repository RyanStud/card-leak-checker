<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Admin - Elevacao de Sessao</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Elevacao de sessao administrativa</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if (!empty($isElevated)): ?>
        <p><strong>Status:</strong> Sessao admin elevada.</p>
        <p><strong>Valido ate:</strong> <?= e(date('Y-m-d H:i:s', (int)$elevatedUntil)) ?></p>
        <p><a href="<?= e(base_path('/admin')) ?>">Ir para dashboard admin</a></p>
    <?php else: ?>
        <p><strong>Status:</strong> Elevacao pendente.</p>
    <?php endif; ?>

    <?php if (($telegramMode ?? 'api') === 'log'): ?>
        <p><strong>Modo local ativo:</strong> TELEGRAM_MODE=log.</p>
        <p>Ao gerar codigo, ele sera salvo no arquivo de log local.</p>

        <form method="POST" action="<?= e(base_path('/admin/elevate/send-code')) ?>" style="margin-bottom: 12px;">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <button type="submit">Gerar codigo local</button>
        </form>

        <form method="POST" action="<?= e(base_path('/admin/elevate/verify')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label>Codigo do log</label><br>
            <input type="text" name="code" maxlength="6" required>
            <button type="submit">Validar e elevar sessao</button>
        </form>
    <?php elseif (empty($telegramLinked)): ?>
        <p>Seu Telegram nao esta vinculado/ativo para elevacao admin.</p>
        <p><a href="<?= e(base_path('/dashboard')) ?>">Ir para Dashboard e vincular Telegram</a></p>
    <?php else: ?>
        <p>Telegram vinculado: <?= e($telegramAccount['telegram_username'] ?? ('ID ' . (string)($telegramAccount['telegram_user_id'] ?? '-'))) ?></p>

        <form method="POST" action="<?= e(base_path('/admin/elevate/send-code')) ?>" style="margin-bottom: 12px;">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <button type="submit">Enviar codigo no Telegram</button>
        </form>

        <form method="POST" action="<?= e(base_path('/admin/elevate/verify')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <label>Codigo recebido no Telegram</label><br>
            <input type="text" name="code" maxlength="6" required>
            <button type="submit">Validar e elevar sessao</button>
        </form>
    <?php endif; ?>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
