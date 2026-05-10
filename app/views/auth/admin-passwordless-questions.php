<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Verificação por perguntas</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Verificação por perguntas de segurança</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <p>Responda as 3 perguntas cadastradas abaixo.</p>

    <form method="POST" action="<?= e(base_path('/admin/passwordless/questions/verify')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <?php foreach (($questions ?? []) as $idx => $q): ?>
            <div style="margin-bottom:12px;">
                <label><strong><?= e($q) ?></strong></label><br>
                <input type="text" name="q_<?= e($idx) ?>" maxlength="255" required />
            </div>
        <?php endforeach; ?>

        <button type="submit">Verificar respostas</button>
    </form>

    <p><a href="<?= e(base_path('/admin/passwordless')) ?>">Voltar</a></p>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
