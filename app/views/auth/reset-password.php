<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Redefinir senha</title>
</head>
<body>
    <h1>Redefinir senha</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <form method="POST" action="<?= e(base_path('/reset-password')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div>
            <label>Nova senha</label><br>
            <input type="password" name="password" required>
        </div>

        <div>
            <label>Confirmar nova senha</label><br>
            <input type="password" name="password_confirmation" required>
        </div>

        <button type="submit">Redefinir senha</button>
    </form>
</body>
</html>