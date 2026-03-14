<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro</title>
</head>
<body>
    <h1>Cadastro</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <form method="POST" action="/card-leak-checker/public/register">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

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

    <p><a href="/card-leak-checker/public/login">Já tenho conta</a></p>
</body>
</html>