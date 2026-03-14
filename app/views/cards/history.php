<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Histórico</title>
</head>
<body>
    <h1>Histórico de verificações</h1>

    <p>
        <a href="/card-leak-checker/public/dashboard">Dashboard</a> |
        <a href="/card-leak-checker/public/projects">Projetos</a> |
        <a href="/card-leak-checker/public/check-card">Nova verificação</a>
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
                    <td><?= htmlspecialchars($row['project_name']) ?></td>
                    <td><?= htmlspecialchars($row['bin_masked']) ?></td>
                    <td><?= htmlspecialchars($row['last4_masked']) ?></td>
                    <td><?= htmlspecialchars($row['result_status']) ?></td>
                    <td><?= htmlspecialchars($row['source_name']) ?></td>
                    <td><?= htmlspecialchars($row['checked_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>