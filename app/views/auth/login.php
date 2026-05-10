<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Login</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
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

        <div class="captcha-box" role="group" aria-label="Desafio captcha">
            <p class="captcha-box__label">Captcha</p>
            <img src="<?= e($captchaImageUrl ?? '') ?>" alt="Imagem CAPTCHA" class="captcha-box__image" loading="lazy">
            <input type="text" name="captcha_answer" placeholder="Digite os caracteres da imagem" required>
            <small>Digite os caracteres que você vê acima (maiúsculas ou minúsculas)</small>
        </div>

        <button type="submit">Entrar</button>
    </form>

    <p><a href="<?= e(base_path('/register')) ?>">Criar conta</a></p>
    <p><a href="<?= e(base_path('/forgot-password')) ?>">Esqueci minha senha</a></p>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>