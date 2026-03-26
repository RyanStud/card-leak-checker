<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Admin - Histórico de Aprovações</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Admin - Histórico de Aprovações</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <h2>Histórico de Aprovações e Rejeições</h2>
    <p>Total de registros: <strong><?= (int)$total ?></strong></p>

    <?php if (empty($history)): ?>
        <p class="no-history">Nenhum histórico de aprovação disponível.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Nome do Projeto</th>
                <th>Proprietário</th>
                <th>Status</th>
                <th>Criado em</th>
                <th>Aprovado em</th>
                <th>Aprovado por</th>
                <th>Motivo / Observações</th>
            </tr>
            <?php foreach ($history as $item): ?>
                <tr>
                    <td><?= (int)$item['id'] ?></td>
                    <td><?= e($item['name']) ?></td>
                    <td>
                        <div><?= e($item['owner_name']) ?></div>
                        <div class="info-row"><?= e($item['owner_email']) ?></div>
                    </td>
                    <td>
                        <?php if ($item['approval_status'] === 'approved'): ?>
                            <span class="status-approved">✓ Aprovado</span>
                        <?php elseif ($item['approval_status'] === 'rejected'): ?>
                            <span class="status-rejected">✗ Rejeitado</span>
                        <?php else: ?>
                            <span><?= e($item['approval_status']) ?></span>
                        <?php endif; ?>
                    </td>
                    <td><?= e($item['project_created_at']) ?></td>
                    <td><?= !empty($item['approved_at']) ? e($item['approved_at']) : '-' ?></td>
                    <td>
                        <?php if (!empty($item['admin_name'])): ?>
                            <div><?= e($item['admin_name']) ?></div>
                            <div class="info-row"><?= e($item['admin_email']) ?></div>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <div><?= e($item['justification'] ?? '-') ?></div>
                        <?php if ($item['approval_status'] === 'rejected' && !empty($item['rejection_reason'])): ?>
                            <div class="rejection-reason"><strong>Rejeição:</strong> <?= e($item['rejection_reason']) ?></div>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="<?= e(base_path('/projects/approval-history?page=1')) ?>">Primeira</a>
                    <a href="<?= e(base_path('/projects/approval-history?page=' . ($page - 1))) ?>">Anterior</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="active"><?= $i ?></span>
                    <?php else: ?>
                        <a href="<?= e(base_path('/projects/approval-history?page=' . $i)) ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="<?= e(base_path('/projects/approval-history?page=' . ($page + 1))) ?>">Próxima</a>
                    <a href="<?= e(base_path('/projects/approval-history?page=' . $totalPages)) ?>">Última</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
