<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Admin - Gestao de usuarios</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>

    <h1>Gestao de usuarios</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <div class="cards-info-box">
        <h2 class="cards-info-box__title">Permissoes e elevacao de privilegio</h2>
        <p class="cards-info-box__intro">A tela exibe poucos registros por pagina, com busca por e-mail. Alteracoes de papel seguem para aprovacao antes de serem aplicadas.</p>
        <ul class="cards-info-box__list">
            <li>Se o usuario ja for admin, apenas a opcao de rebaixar fica disponivel.</li>
            <li>Se o usuario for user, apenas a opcao de promover fica disponivel.</li>
            <li>Promocao para admin exige Telegram vinculado e ativo.</li>
            <li>O proprio admin logado nao pode remover a propria permissao.</li>
        </ul>
    </div>

    <form class="filter-form" method="GET" action="<?= e(base_path('/admin/users')) ?>">
        <label for="email"><strong>Buscar por e-mail:</strong></label>
        <input id="email" type="email" name="email" value="<?= e($emailFilter ?? '') ?>" placeholder="usuario@dominio.com">
        <button type="submit">Buscar</button>
    </form>

    <h2>Solicitacoes pendentes de aprovacao</h2>
    <?php if (empty($pendingRequests)): ?>
        <p class="no-pending">Nenhuma solicitacao pendente no momento.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Solicitado por</th>
                <th>Usuario alvo</th>
                <th>Atual</th>
                <th>Solicitado</th>
                <th>Data</th>
                <th>Acoes</th>
            </tr>
            <?php foreach ($pendingRequests as $req): ?>
                <tr>
                    <td><?= (int)$req['id'] ?></td>
                    <td><?= e(($req['requester_name'] ?? '-') . ' (' . ($req['requester_email'] ?? '-') . ')') ?></td>
                    <td><?= e(($req['target_name'] ?? '-') . ' (' . ($req['target_email'] ?? '-') . ')') ?></td>
                    <td><?= e($req['from_role'] ?? '-') ?></td>
                    <td><?= e($req['to_role'] ?? '-') ?></td>
                    <td><?= e($req['created_at'] ?? '-') ?></td>
                    <td class="actions">
                        <form method="POST" action="<?= e(base_path('/admin/users/role/approve')) ?>">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                            <button type="submit" class="approve">Aprovar</button>
                        </form>
                        <form method="POST" action="<?= e(base_path('/admin/users/role/reject')) ?>">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="request_id" value="<?= (int)$req['id'] ?>">
                            <button type="submit" class="reject">Rejeitar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h2>Usuarios</h2>

    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Papel atual</th>
            <th>E-mail verificado</th>
            <th>2FA</th>
            <th>Telegram</th>
            <th>Acoes</th>
        </tr>
        <?php foreach ($users as $item): ?>
            <?php
            $telegramOk = !empty($item['telegram_user_id']) && !empty($item['telegram_is_active']);
            $isCurrent = (int)$item['id'] === (int)$currentUserId;
            $role = (string)($item['role'] ?? 'user');
            ?>
            <tr>
                <td><?= (int)$item['id'] ?></td>
                <td><?= e($item['name']) ?></td>
                <td><?= e($item['email']) ?></td>
                <td><?= e($role) ?></td>
                <td><?= !empty($item['email_verified']) ? 'Sim' : 'Nao' ?></td>
                <td><?= !empty($item['two_factor_enabled']) ? 'Sim' : 'Nao' ?></td>
                <td>
                    <?php if ($telegramOk): ?>
                        <span class="ok">Ativo</span>
                        <br>
                        <small><?= e($item['telegram_username'] ?? '') ?></small>
                    <?php else: ?>
                        <span class="danger">Nao vinculado/ativo</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <?php if ($role === 'user'): ?>
                        <form method="POST" action="<?= e(base_path('/admin/users/role')) ?>">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="target_user_id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="role" value="admin">
                            <button type="submit" class="approve">Solicitar promocao</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="<?= e(base_path('/admin/users/role')) ?>">
                            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="target_user_id" value="<?= (int)$item['id'] ?>">
                            <input type="hidden" name="role" value="user">
                            <button type="submit" class="reject" <?= $isCurrent ? 'disabled' : '' ?>>Solicitar rebaixamento</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <?php if (($totalPages ?? 1) > 1): ?>
        <nav class="pagination" aria-label="Paginacao de usuarios">
            <?php for ($p = 1; $p <= (int)$totalPages; $p++): ?>
                <?php if ($p === (int)$page): ?>
                    <span class="active"><?= $p ?></span>
                <?php else: ?>
                    <a href="<?= e(base_path('/admin/users?page=' . $p . '&email=' . urlencode((string)($emailFilter ?? '')))) ?>"><?= $p ?></a>
                <?php endif; ?>
            <?php endfor; ?>
        </nav>
    <?php endif; ?>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
