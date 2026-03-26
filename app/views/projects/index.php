<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <title>Projetos</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Projetos</h1>

    <p>
        <a href="<?= e(base_path('/dashboard')) ?>">Dashboard</a> |
        <a href="<?= e(base_path('/check-card')) ?>">Verificar cartão</a> |
        <a href="<?= e(base_path('/cards/history')) ?>">Histórico</a>
    </p>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <h2>Criar novo projeto</h2>

    <form method="POST" action="<?= e(base_path('/projects')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
            <label>Nome do projeto</label><br>
            <input type="text" name="name" required>
        </div>

        <div>
            <label>Privacidade</label><br>
            <select name="privacy_mode">
                <option value="private">Privado</option>
                <option value="restricted">Restrito</option>
            </select>
        </div>

        <div>
            <label>Justificativa da criação do projeto</label><br>
            <textarea name="justification" rows="4" cols="60" minlength="20" required></textarea>
        </div>

        <div style="margin-top:8px;">
            <label>
                <input type="checkbox" name="terms_agree" value="1" required>
                Eu me comprometo a utilizar as informações de cartão de crédito apenas para fins legítimos e justos.
            </label>
        </div>

        <button type="submit">Criar projeto</button>
    </form>

    <h2>Meus projetos</h2>

    <?php if (empty($projects)): ?>
        <p>Nenhum projeto cadastrado.</p>
    <?php else: ?>
        <table border="1" cellpadding="8">
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Slug</th>
                <th>Privacidade</th>
                <th>Papel</th>
                <th>Status</th>
                <th>Criado em</th>
                <th>Compartilhar</th>
            </tr>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?= (int)$project['id'] ?></td>
                    <td><?= e($project['name']) ?></td>
                    <td><?= e($project['slug']) ?></td>
                    <td><?= e($project['privacy_mode']) ?></td>
                    <td><?= e($project['role']) ?></td>
                    <td>
                        <?php
                            $status = $project['approval_status'] ?? 'pending';
                            if ($status === 'approved') {
                                echo '<span style="color: green; font-weight: bold;">✓ Aprovado</span>';
                            } elseif ($status === 'pending') {
                                echo '<span style="color: orange; font-weight: bold;">⏳ Pendente</span>';
                            } elseif ($status === 'rejected') {
                                $rejection_reason = $project['rejection_reason'] ?? 'Não informado';
                                echo '<span style="color: red; font-weight: bold;">✗ Rejeitado</span>';
                                echo '<br><small style="color: #666;">Motivo: ' . e($rejection_reason) . '</small>';
                            }
                        ?>
                    </td>
                    <td><?= e($project['created_at']) ?></td>
                    <td>
                        <?php if (($project['privacy_mode'] ?? 'private') === 'restricted' && ($project['role'] ?? 'member') === 'owner'): ?>
                            <form method="POST" action="<?= e(base_path('/projects/share')) ?>" style="display:flex; gap:8px; align-items:center;">
                                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="project_id" value="<?= (int)$project['id'] ?>">
                                <input type="email" name="email" placeholder="email@usuario.com" required>
                                <button type="submit">Compartilhar</button>
                            </form>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>