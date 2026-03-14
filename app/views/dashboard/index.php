<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
</head>
<body>
    <h1>Dashboard</h1>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <p>Bem-vindo, <strong><?= htmlspecialchars($user['name'] ?? 'Usuário') ?></strong></p>
    <p>E-mail: <?= htmlspecialchars($user['email'] ?? '') ?></p>
    <p>2FA ativo: <?= !empty($user['two_factor_enabled']) ? 'Sim' : 'Não' ?></p>

    <p>
        <a href="/card-leak-checker/public/projects">Projetos</a> |
        <a href="/card-leak-checker/public/check-card">Verificar cartão</a> |
        <a href="/card-leak-checker/public/cards/history">Histórico</a> |
        <a href="/card-leak-checker/public/privacy">Privacidade / LGPD</a>
    </p>

    <form method="POST" action="/card-leak-checker/public/logout">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
        <button type="submit">Sair</button>
    </form>
</body>
</html>