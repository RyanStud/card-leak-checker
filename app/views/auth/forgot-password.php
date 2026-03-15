<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Esqueci minha senha</title>
</head>
<body>
    <h1>Recuperação de senha</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <p>Informe seu e-mail para gerar um link de redefinição.</p>

    <form method="POST" action="/card-leak-checker/public/forgot-password">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

        <div>
            <label>E-mail</label><br>
            <input type="email" name="email" required>
        </div>

        <button type="submit">Gerar link</button>
    </form>

    <p><a href="/card-leak-checker/public/login">Voltar ao login</a></p>
</body>
</html>