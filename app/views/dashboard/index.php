<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Dashboard</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Dashboard</h1>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <p>Bem-vindo, <strong><?= e($user['name'] ?? 'Usuário') ?></strong></p>
    <p>E-mail: <?= e($user['email'] ?? '') ?></p>
    <p>Papel: <?= e($user['role'] ?? 'user') ?></p>
    <p>2FA ativo: <?= !empty($user['two_factor_enabled']) ? 'Sim' : 'Não' ?></p>
    
</body>
</html>