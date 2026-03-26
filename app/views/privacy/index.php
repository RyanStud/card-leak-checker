<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Privacidade e LGPD</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Privacidade e LGPD</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <h2>Dados do usuário no sistema</h2>

    <?php if (!empty($profile)): ?>
        <ul>
            <li><strong>ID:</strong> <?= (int)$profile['id'] ?></li>
            <li><strong>Nome:</strong> <?= e($profile['name']) ?></li>
            <li><strong>E-mail:</strong> <?= e($profile['email']) ?></li>
            <li><strong>E-mail verificado:</strong> <?= !empty($profile['email_verified']) ? 'Sim' : 'Não' ?></li>
            <li><strong>2FA ativo:</strong> <?= !empty($profile['two_factor_enabled']) ? 'Sim' : 'Não' ?></li>
            <li><strong>Criado em:</strong> <?= e($profile['created_at']) ?></li>
        </ul>
    <?php endif; ?>

    <h2>Resumo de dados</h2>
    <ul>
        <li><strong>Total de verificações:</strong> <?= (int)$historyCount ?></li>
        <li><strong>Total de projetos próprios:</strong> <?= (int)$projectsCount ?></li>
    </ul>

    <hr>

    <h2>Exclusão parcial de dados</h2>

    <h3>1. Apagar histórico de verificações</h3>
    <p>Remove todos os registros da tabela de verificações vinculados ao seu usuário.</p>

    <form method="POST" action="<?= e(base_path('/privacy/delete-history')) ?>"
          onsubmit="return confirm('Deseja realmente apagar seu histórico de verificações?');">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit">Apagar histórico</button>
    </form>

    <br>

    <h3>2. Apagar projetos próprios</h3>
    <p>Remove os projetos que você criou. Isso pode apagar também dados relacionados por cascade.</p>

    <form method="POST" action="<?= e(base_path('/privacy/delete-projects')) ?>"
          onsubmit="return confirm('Deseja realmente apagar todos os seus projetos próprios?');">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit">Apagar projetos próprios</button>
    </form>

    <hr>

    <h2>Exclusão total da conta</h2>
    <p>
        Esta ação exclui sua conta e dados relacionados.
        Para maior segurança, confirme com sua senha e com o código do Google Authenticator.
    </p>

    <form method="POST" action="<?= e(base_path('/privacy/delete-account')) ?>"
          onsubmit="return confirm('Tem certeza que deseja excluir sua conta definitivamente?');">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
            <label>Confirme sua senha</label><br>
            <input type="password" name="password" required>
        </div>

        <div>
            <label>Código do Google Authenticator</label><br>
            <input type="text" name="code" maxlength="6" required>
        </div>

        <br>
        <button type="submit" style="color:red;">Excluir conta definitivamente</button>
    </form>
</body>
</html>