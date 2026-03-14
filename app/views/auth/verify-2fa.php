<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Validar 2FA</title>
</head>
<body>
    <h1>Validação em 2 fatores</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <form method="POST" action="/card-leak-checker/public/2fa/verify">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <label>Código do Google Authenticator</label><br>
        <input type="text" name="code" maxlength="6" required>
        <button type="submit">Validar</button>
    </form>
</body>
</html>