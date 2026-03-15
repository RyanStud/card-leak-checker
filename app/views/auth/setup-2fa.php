<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurar 2FA</title>
</head>
<body>
    <h1>Configurar Google Authenticator</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <p>Conta: <strong><?= e($email) ?></strong></p>
    <p>Adicione manualmente no Google Authenticator com esta chave:</p>

    <pre><?= e($secret) ?></pre>

    <p>URI TOTP:</p>
    <textarea rows="4" cols="100" readonly><?= e($otpauth) ?></textarea>

    <p>Depois digite o código gerado no app:</p>

    <form method="POST" action="<?= e(base_path('/2fa/setup')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="text" name="code" maxlength="6" required>
        <button type="submit">Ativar 2FA</button>
    </form>
</body>
</html>