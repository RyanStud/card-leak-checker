<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <style>
        .projects-container {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }

        .projects-header {
            text-align: center;
            margin-bottom: 20px;
        }

        .projects-header h1 {
            margin-bottom: 8px;
        }

        .projects-header p {
            color: var(--muted);
            font-size: 0.95rem;
            margin: 0;
        }

        .projects-menu {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin: 0 auto;
            max-width: 720px;
            width: 100%;
        }

        .menu-card {
            background: var(--surface);
            border: 1px solid rgba(216, 227, 239, 0.95);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .menu-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 25px 50px rgba(12, 45, 76, 0.15);
        }

        .menu-card h3 {
            margin: 0 0 12px 0;
            font-size: 1.25rem;
        }

        .menu-card p {
            color: var(--muted);
            font-size: 0.9rem;
            margin: 0 0 16px 0;
        }

        .btn-menu {
            background: var(--primary);
            color: white;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            width: 100%;
        }

        .btn-menu:hover {
            background: var(--primary-strong);
        }

        .create-project-form {
            max-width: 640px;
            margin: 0 auto;
            display: none;
        }

        .create-project-form.active {
            display: block;
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close-form {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--muted);
        }

        .projects-list {
            margin-top: 20px;
        }

        .alert {
            padding: 14px 16px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-weight: 500;
        }

        .alert-error {
            background: rgba(181, 58, 46, 0.1);
            border: 1px solid rgba(181, 58, 46, 0.3);
            color: var(--danger);
        }

        .alert-success {
            background: rgba(29, 127, 72, 0.1);
            border: 1px solid rgba(29, 127, 72, 0.3);
            color: var(--success);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        table th,
        table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--line);
        }

        table th {
            background: rgba(12, 123, 147, 0.05);
            font-weight: 700;
            color: #172436;
        }

        table tr:hover {
            background: rgba(12, 123, 147, 0.03);
        }

        .status-approved {
            color: var(--success);
            font-weight: bold;
        }

        .status-pending {
            color: var(--warning);
            font-weight: bold;
        }

        .status-rejected {
            color: var(--danger);
            font-weight: bold;
        }

        .table-actions {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }

        details > summary {
            cursor: pointer;
            font-weight: 600;
            color: var(--primary);
            padding: 8px 0;
        }

        details > summary:hover {
            text-decoration: underline;
        }

        .details-table {
            margin-top: 12px;
            background: rgba(12, 123, 147, 0.02);
            border-radius: 8px;
            overflow: auto;
        }

        .details-table table {
            margin: 0;
            font-size: 0.9rem;
        }

        .btn-small {
            background: var(--primary);
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-small:hover {
            background: var(--primary-strong);
        }

        .input-email {
            width: 100%;
            max-width: none;
        }
    </style>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>

    <div class="projects-container">
        <div class="projects-header">
            <h1>Projetos</h1>
            <p>Gerencie seus projetos de análise de cartões de crédito</p>
        </div>

        <?php if ($msg = flash('error')): ?>
            <div class="alert alert-error"><?= e($msg) ?></div>
        <?php endif; ?>

        <?php if ($msg = flash('success')): ?>
            <div class="alert alert-success"><?= e($msg) ?></div>
        <?php endif; ?>

        <div class="projects-menu">
            <div class="menu-card">
                <h3>➕ Novo</h3>
                <p>Criar um novo projeto para análise</p>
                <button type="button" class="btn-menu btn-create-project">Criar Projeto</button>
            </div>

            <div class="menu-card">
                <h3>📋 Meus</h3>
                <p>Ver seus projetos cadastrados</p>
                <button type="button" class="btn-menu btn-scroll-projects">Meus Projetos</button>
            </div>
        </div>

        <div class="create-project-form">
            <div style="background: var(--surface); border: 1px solid rgba(216, 227, 239, 0.95); border-radius: var(--radius); padding: 24px;">
                <div class="form-header">
                    <h2 style="margin: 0;">Criar novo projeto</h2>
                    <button type="button" class="close-form btn-close-form">✕</button>
                </div>

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

                    <div style="margin-top:12px;">
                        <label>
                            <input type="checkbox" name="terms_agree" value="1" required>
                            Eu me comprometo a utilizar as informações de cartão de crédito apenas para fins legítimos e justos.
                        </label>
                    </div>

                    <button type="submit" class="btn-small" style="width: 100%; padding: 10px; font-size: 1rem; margin-top: 16px;">Criar projeto</button>
                </form>
            </div>
        </div>

        <div class="projects-list">
            <h2>Meus Projetos</h2>


            <?php if (empty($projects)): ?>
                <p style="text-align: center; color: var(--muted); padding: 32px 0;">Nenhum projeto cadastrado.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Privacidade</th>
                            <th>Papel</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th style="width: 150px;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $project): 
                            $projectId = (int)$project['id'];
                            $isRestricted = ($project['privacy_mode'] ?? 'private') === 'restricted';
                            $isOwner = ($project['role'] ?? 'member') === 'owner';
                        ?>
                            <tr data-project-row="<?= $projectId ?>">
                                <td><strong><?= e($project['name']) ?></strong></td>
                                <td><?= ucfirst(e($project['privacy_mode'])) ?></td>
                                <td><?= ucfirst(e($project['role'])) ?></td>
                                <td>
                                    <?php
                                        $status = $project['approval_status'] ?? 'pending';
                                        if ($status === 'approved') {
                                            echo '<span class="status-approved">✓ Aprovado</span>';
                                        } elseif ($status === 'pending') {
                                            echo '<span class="status-pending">⏳ Pendente</span>';
                                        } elseif ($status === 'rejected') {
                                            $rejection_reason = $project['rejection_reason'] ?? 'Não informado';
                                            echo '<span class="status-rejected">✗ Rejeitado</span>';
                                        }
                                    ?>
                                </td>
                                <td><?= e($project['created_at']) ?></td>
                                <td>
                                    <div class="table-actions">
                                        <?php if ($isRestricted && $isOwner): ?>
                                            <button type="button" class="btn-small btn-share" data-project="<?= $projectId ?>">Compartilhar</button>
                                        <?php endif; ?>
                                        <button type="button" class="btn-small btn-access" data-project="<?= $projectId ?>">Acessos</button>
                                    </div>
                                </td>
                            </tr>
                            <?php if ($isRestricted && $isOwner): ?>
                                <tr class="share-form-row" data-share-row="<?= $projectId ?>" style="display: none;">
                                    <td colspan="6">
                                        <form method="POST" action="<?= e(base_path('/projects/share')) ?>" style="display: flex; gap: 8px; align-items: flex-end; margin: 0;">
                                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                            <div style="flex: 1;">
                                                <label style="display: block; margin-bottom: 6px; font-size: 0.9rem;">E-mail do usuário</label>
                                                <input type="email" name="email" placeholder="email@usuario.com" required class="input-email">
                                            </div>
                                            <button type="submit" class="btn-small">Compartilhar</button>
                                            <button type="button" class="btn-small btn-cancel-share" style="background: #999;">Cancelar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <tr class="access-details-row" data-access-row="<?= $projectId ?>" style="display: none;">
                                <td colspan="6">
                                    <div class="details-table">
                                        <?php if ($isRestricted && $isOwner): ?>
                                            <?php $projectMembers = $membersByProjectId[$projectId] ?? []; ?>
                                            <?php if (empty($projectMembers)): ?>
                                                <p style="padding: 16px; margin: 0; color: var(--muted);">Nenhum acesso encontrado.</p>
                                            <?php else: ?>
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th>Usuário</th>
                                                            <th>E-mail</th>
                                                            <th>Papel</th>
                                                            <th style="width: 100px;">Ação</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($projectMembers as $member): ?>
                                                            <tr>
                                                                <td><?= e($member['name'] ?? '-') ?></td>
                                                                <td><?= e($member['email'] ?? '-') ?></td>
                                                                <td><?= ucfirst(e($member['role'] ?? 'member')) ?></td>
                                                                <td>
                                                                    <?php if (($member['role'] ?? 'member') === 'owner'): ?>
                                                                        <span style="color: var(--muted); font-size: 0.85rem;">Dono</span>
                                                                    <?php else: ?>
                                                                        <form method="POST" action="<?= e(base_path('/projects/revoke-access')) ?>" style="margin: 0;" onsubmit="return confirm('Remover o acesso deste usuário?');">
                                                                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                                                                            <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                                                            <input type="hidden" name="target_user_id" value="<?= (int)$member['user_id'] ?>">
                                                                            <button type="submit" class="btn-small" style="background: var(--danger);">Remover</button>
                                                                        </form>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <p style="padding: 16px; margin: 0; color: var(--muted);">Este projeto não permite compartilhamento.</p>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script src="<?= e(base_path('/public/js/projects.js')) ?>"></script>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>