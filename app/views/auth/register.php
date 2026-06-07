<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Cadastro</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Cadastro</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <form
        id="register-form"
        method="POST"
        action="<?= e(base_path('/register')) ?>"
        data-pubkey-url="<?= e(base_path('/crypto/public-key')) ?>"
    >
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
            <label>Nome</label><br>
            <input type="text" name="name" value="<?= old('name') ?>" required>
        </div>

        <div>
            <label>E-mail</label><br>
            <input type="email" name="email" value="<?= old('email') ?>" required>
        </div>

        <div>
            <label>Senha forte</label><br>
            <input type="password" name="password" required>
        </div>

        <div class="captcha-box" role="group" aria-label="Desafio captcha">
            <p class="captcha-box__label">Captcha</p>
            <img src="<?= e($captchaImageUrl ?? '') ?>" alt="Imagem CAPTCHA" class="captcha-box__image" loading="lazy">
            <input type="text" name="captcha_answer" placeholder="Digite os caracteres da imagem" required>
            <small>Digite os caracteres que você vê acima (maiúsculas ou minúsculas)</small>
        </div>

        <div>
            <label>
                <input
                    type="checkbox"
                    name="lgpd_consent"
                    value="1"
                    <?= old('lgpd_consent') === '1' ? 'checked' : '' ?>
                    required
                >
                Li e concordo com o tratamento dos meus dados pessoais, conforme a LGPD.
            </label>
        </div>

        <button type="submit">Cadastrar</button>

        <p id="crypto-status" style="color:#555;font-size:0.9em;" aria-live="polite"></p>
    </form>

    <p><a href="<?= e(base_path('/login')) ?>">Já tenho conta</a></p>
    <script src="<?= e(base_path('/public/js/hybrid-register.js')) ?>" defer></script>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>