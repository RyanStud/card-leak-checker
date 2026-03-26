<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <title>Histórico</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Histórico de verificações</h1>

    <p>
        <a href="<?= e(base_path('/dashboard')) ?>">Dashboard</a> |
        <a href="<?= e(base_path('/projects')) ?>">Projetos</a> |
        <a href="<?= e(base_path('/check-card')) ?>">Nova verificação</a>
    </p>

    <?php if (empty($history)): ?>
        <p>Nenhuma verificação registrada.</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>ID</th>
                <th>Projeto</th>
                <th>BIN mascarado</th>
                <th>Últimos 4</th>
                <th>Status</th>
                <th>Origem</th>
                <th>Data</th>
            </tr>
            <?php foreach ($history as $row): ?>
                <tr>
                    <td><?= (int)$row['id'] ?></td>
                    <td><?= e($row['project_name']) ?></td>
                    <td><?= e($row['bin_masked']) ?></td>
                    <td><?= e($row['last4_masked']) ?></td>
                    <td><?= e($row['result_status']) ?></td>
                    <td><?= e($row['source_name']) ?></td>
                    <td><?= e($row['checked_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>