<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <form method="POST" action="<?= e(base_path('/login')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
            <label>E-mail</label><br>
            <input type="email" name="email" required>
        </div>

        <div>
            <label>Senha</label><br>
            <input type="password" name="password" required>
        </div>

        <button type="submit">Entrar</button>
    </form>

    <p><a href="<?= e(base_path('/register')) ?>">Criar conta</a></p>
    <p><a href="<?= e(base_path('/forgot-password')) ?>">Esqueci minha senha</a></p>
</body>
</html>