<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Verificacao de acesso</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Verificacao de acesso</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <h2>1. Solicitar codigo</h2>
    <form method="POST" action="<?= e(base_path('/admin/passwordless/request')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
            <label>E-mail</label><br>
            <input type="email" name="email" required>
        </div>

        <div class="captcha-box" role="group" aria-label="Desafio captcha">
            <p class="captcha-box__label">Captcha</p>
            <p class="captcha-box__question"><?= e($captchaQuestion ?? '') ?></p>
            <input type="text" name="captcha_answer" inputmode="numeric" placeholder="Digite o resultado" required>
        </div>

        <button type="submit">Enviar codigo</button>
    </form>

    <h2>2. Validar codigo</h2>
    <form method="POST" action="<?= e(base_path('/admin/passwordless/verify')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
            <label>Codigo recebido</label><br>
            <input type="text" name="code" maxlength="6" required>
        </div>

        <button type="submit">Entrar</button>
    </form>

    <p><a href="<?= e(base_path('/login')) ?>">Voltar ao login</a></p>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
