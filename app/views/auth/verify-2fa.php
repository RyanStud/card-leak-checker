<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Validar 2FA</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Validação em 2 fatores</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <form method="POST" action="<?= e(base_path('/2fa/verify')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <label>Código do Google Authenticator</label><br>
        <input type="text" name="code" maxlength="6" required>
        <button type="submit">Validar</button>
    </form>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>