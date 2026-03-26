<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <title>Admin - Aprovar Projetos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .nav a { margin-right: 12px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #efefef; }
        .justification { max-width: 400px; word-wrap: break-word; }
        .actions { display: flex; gap: 8px; }
        .actions button { padding: 4px 8px; font-size: 12px; }
        .approve { background: #28a745; color: white; border: none; cursor: pointer; }
        .reject { background: #dc3545; color: white; border: none; cursor: pointer; }
        .no-pending { color: #666; font-style: italic; }
        .modal { display: none; position: fixed; z-index: 1; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.4); }
        .modal.show { display: block; }
        .modal-content { background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; }
        .modal-header { font-size: 18px; font-weight: bold; margin-bottom: 15px; }
        .modal-buttons { margin-top: 20px; display: flex; gap: 10px; justify-content: flex-end; }
        .modal-buttons button { padding: 8px 16px; cursor: pointer; }
        .modal-buttons .cancel { background: #6c757d; color: white; border: none; }
        .modal-buttons .confirm { background: #dc3545; color: white; border: none; }
        textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Admin - Aprovar Projetos</h1>

    <p class="nav">
        <a href="<?= e(base_path('/admin')) ?>">Dashboard</a> |
        <a href="<?= e(base_path('/projects/approval-history')) ?>">Histórico</a> |
        <a href="<?= e(base_path('/dashboard')) ?>">Meu Dashboard</a>
    </p>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <h2>Projetos Pendentes de Aprovação</h2>

    <?php if (empty($projects)): ?>
        <p class="no-pending">Nenhum projeto pendente de aprovação.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Nome do Projeto</th>
                <th>Proprietário</th>
                <th>E-mail</th>
                <th>Privacidade</th>
                <th>Justificativa</th>
                <th>Criado em</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?= (int)$project['id'] ?></td>
                    <td><?= e($project['name']) ?></td>
                    <td><?= e($project['name'] ?? '-') ?></td>
                    <td><?= e($project['email'] ?? '-') ?></td>
                    <td><?= e($project['privacy_mode']) ?></td>
                    <td class="justification"><?= e($project['justification'] ?? '-') ?></td>
                    <td><?= e($project['created_at']) ?></td>
                    <td>
                        <div class="actions">
                            <form method="POST" action="<?= e(base_path('/projects/approve')) ?>" style="display:inline;">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">
                                <button type="submit" class="approve">Aprovar</button>
                            </form>
                            <form method="POST" action="<?= e(base_path('/projects/reject')) ?>" style="display:inline;" id="rejectForm_<?= (int)$project['id'] ?>" data-project-id="<?= (int)$project['id'] ?>">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">
                                <input type="hidden" name="rejection_reason" id="rejectionReason_<?= (int)$project['id'] ?>" value="">
                                <button type="button" class="reject" data-open-reject-modal="<?= (int)$project['id'] ?>">Rejeitar</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">Rejeitar Projeto</div>
            <p>Informe o motivo da rejeição:</p>
            <textarea id="rejectionReasonInput" placeholder="Digite o motivo pelo qual o projeto foi rejeitado..." rows="5"></textarea>
            <div class="modal-buttons">
                <button class="cancel" id="modalCancelBtn">Cancelar</button>
                <button class="confirm" id="modalConfirmBtn">Confirmar Rejeição</button>
            </div>
        </div>
    </div>

    <script src="<?= e(base_path('/public/js/reject-modal.js')) ?>"></script>
</body>
</html>
