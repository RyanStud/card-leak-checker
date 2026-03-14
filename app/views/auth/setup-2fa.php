<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Configurar 2FA</title>
</head>
<body>
    <h1>Configurar Google Authenticator</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <p>Conta: <strong><?= htmlspecialchars($email) ?></strong></p>
    <p>Adicione manualmente no Google Authenticator com esta chave:</p>

    <pre><?= htmlspecialchars($secret) ?></pre>

    <p>URI TOTP:</p>
    <textarea rows="4" cols="100" readonly><?= htmlspecialchars($otpauth) ?></textarea>

    <p>Depois digite o código gerado no app:</p>

    <form method="POST" action="/card-leak-checker/public/2fa/setup">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <input type="text" name="code" maxlength="6" required>
        <button type="submit">Ativar 2FA</button>
    </form>
</body>
</html>