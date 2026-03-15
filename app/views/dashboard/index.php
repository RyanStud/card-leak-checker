<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Dashboard</h1>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <p>Bem-vindo, <strong><?= e($user['name'] ?? 'Usuário') ?></strong></p>
    <p>E-mail: <?= e($user['email'] ?? '') ?></p>
    <p>Papel: <?= e($user['role'] ?? 'user') ?></p>
    <p>2FA ativo: <?= !empty($user['two_factor_enabled']) ? 'Sim' : 'Não' ?></p>
    
    <p>
        <a href="<?= e(base_path('/projects')) ?>">Projetos</a> |
        <a href="<?= e(base_path('/check-card')) ?>">Verificar cartão</a> |
        <a href="<?= e(base_path('/cards/history')) ?>">Histórico</a> |
        <a href="<?= e(base_path('/privacy')) ?>">Privacidade / LGPD</a>
        <?php if (($user['role'] ?? 'user') === 'admin'): ?>
            | <a href="<?= e(base_path('/admin')) ?>">Admin / Segurança</a>
        <?php endif; ?>
    </p>

    <form method="POST" action="<?= e(base_path('/logout')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit">Sair</button>
    </form>
</body>
</html>