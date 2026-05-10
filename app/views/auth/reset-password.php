<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Redefinir senha</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
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

        <div class="captcha-box" role="group" aria-label="Desafio captcha">
            <p class="captcha-box__label">Captcha</p>
            <img src="<?= e($captchaImageUrl ?? '') ?>" alt="Imagem CAPTCHA" class="captcha-box__image" loading="lazy">
            <input type="text" name="captcha_answer" placeholder="Digite os caracteres da imagem" required>
            <small>Digite os caracteres que você vê acima (maiúsculas ou minúsculas)</small>
        </div>

        <button type="submit">Redefinir senha</button>
    </form>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>