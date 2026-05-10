<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <?php if (!empty($codeSentAt) && is_int($codeSentAt)): ?>
        <meta name="admin-passwordless-sent-at" content="<?= e((int)$codeSentAt) ?>">
    <?php endif; ?>
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
            <img src="<?= e($captchaImageUrl ?? '') ?>" alt="Imagem CAPTCHA" class="captcha-box__image" loading="lazy">
            <input type="text" name="captcha_answer" placeholder="Digite os caracteres da imagem" required>
            <small>Digite os caracteres que você vê acima (maiúsculas ou minúsculas)</small>
        </div>

        <button type="submit">Enviar codigo</button>
    </form>

    <?php $canUse = $canUseQuestions ?? false; $sentAt = $codeSentAt ?? 0; ?>
    <?php if ($canUse): ?>
        <div style="margin-top:14px;">
            <form id="use-questions-form" method="GET" action="<?= e(base_path('/admin/passwordless/questions')) ?>">
                <button id="use-questions-btn" type="submit" disabled>Usar perguntas de segurança (disponível após 60s)</button>
            </form>
            <small id="questions-timer-note" style="margin-left:12px; color:var(--muted);"><?php if ($sentAt>0) { echo 'Código enviado há: <span id="sent-age">calculando</span>'; } ?></small>
        </div>
        <script src="<?= e(base_path('/public/js/admin-passwordless.js')) ?>" defer></script>
    <?php endif; ?>

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
