<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Esqueci minha senha</title>
</head>
<body>
    <h1>Recuperação de senha</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <p>Informe seu e-mail para gerar um link de redefinição.</p>

    <form method="POST" action="<?= e(base_path('/forgot-password')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
            <label>E-mail</label><br>
            <input type="email" name="email" required>
        </div>

        <button type="submit">Gerar link</button>
    </form>

    <p><a href="<?= e(base_path('/login')) ?>">Voltar ao login</a></p>
</body>
</html>