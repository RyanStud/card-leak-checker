<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <title>Cadastro</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Cadastro</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <form method="POST" action="<?= e(base_path('/register')) ?>">
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

        <button type="submit">Cadastrar</button>
    </form>

    <p><a href="<?= e(base_path('/login')) ?>">Já tenho conta</a></p>
</body>
</html>