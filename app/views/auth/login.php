<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <form method="POST" action="/card-leak-checker/public/login">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

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

    <p><a href="/card-leak-checker/public/register">Criar conta</a></p>
</body>
</html>