<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmação de cadastro</title>
</head>
<body>
    <h1>Confirme seu e-mail</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <p>
        Enviamos um e-mail de confirmação. Abra o link enviado para ativar sua conta.
    </p>

    <p>
        Após confirmar, volte para a tela de login.
    </p>

    <p><a href="<?= e(base_path('/login')) ?>">Ir para login</a></p>
</body>
</html>
