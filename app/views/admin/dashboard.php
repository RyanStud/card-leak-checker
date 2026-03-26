<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Admin - Dashboard de Seguranca</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Admin - Dashboard de Seguranca</h1>

    <form class="filter-form" method="GET" action="<?= e(base_path('/admin')) ?>">
        <label for="range"><strong>Periodo de exibicao:</strong></label>
        <select id="range" name="range">
            <?php foreach ($allowedRanges as $key => $range): ?>
                <option value="<?= e($key) ?>" <?= $selectedRange === $key ? 'selected' : '' ?>>
                    <?= e($range['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Aplicar</button>
    </form>

    <p class="small">Exibindo dados de: <strong><?= e($allowedRanges[$selectedRange]['label'] ?? '') ?></strong></p>

    <h2>Visao geral</h2>
    <div class="cards">
        <div class="card"><strong>Usuarios</strong><br><?= (int)$counts['users'] ?></div>
        <div class="card"><strong>Projetos</strong><br><?= (int)$counts['projects'] ?></div>
        <div class="card"><strong>Verificacoes</strong><br><?= (int)$counts['card_checks'] ?></div>
        <div class="card"><strong>Logs de auditoria</strong><br><?= (int)$counts['audit_logs'] ?></div>
        <div class="card"><strong>Tentativas de login</strong><br><?= (int)$counts['login_attempts'] ?></div>
        <div class="card"><strong>Eventos suspeitos</strong><br><?= (int)$counts['suspicious_events'] ?></div>
        <div class="card"><strong>Resets de senha</strong><br><?= (int)$counts['password_resets'] ?></div>
        <div class="card"><strong>IPs bloqueados</strong><br><?= (int)$counts['blocked_ips'] ?></div>
        <div class="card"><strong>Requisicoes</strong><br><?= (int)$counts['request_logs'] ?></div>
    </div>

    <h2>Usuarios cadastrados</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Nome</th>
            <th>E-mail</th>
            <th>Papel</th>
            <th>E-mail verificado</th>
            <th>2FA</th>
            <th>Criado em</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?= (int)$user['id'] ?></td>
                <td><?= e($user['name']) ?></td>
                <td><?= e($user['email']) ?></td>
                <td><?= e($user['role']) ?></td>
                <td><?= !empty($user['email_verified']) ? 'Sim' : 'Nao' ?></td>
                <td><?= !empty($user['two_factor_enabled']) ? 'Sim' : 'Nao' ?></td>
                <td><?= e($user['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Top IPs em tentativas de login</h2>
    <table>
        <tr>
            <th>IP</th>
            <th>Total</th>
        </tr>
        <?php foreach ($topIps as $row): ?>
            <tr>
                <td><?= e($row['ip_address']) ?></td>
                <td><?= (int)$row['total'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Tipos de eventos suspeitos</h2>
    <table>
        <tr>
            <th>Tipo</th>
            <th>Total</th>
        </tr>
        <?php foreach ($suspiciousTypes as $row): ?>
            <tr>
                <td><?= e($row['event_type']) ?></td>
                <td><?= (int)$row['total'] ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Eventos suspeitos recentes</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>User ID</th>
            <th>E-mail</th>
            <th>IP</th>
            <th>Evento</th>
            <th>Detalhes</th>
            <th>Criado em</th>
        </tr>
        <?php foreach ($suspiciousEvents as $row): ?>
            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= $row['user_id'] !== null ? (int)$row['user_id'] : '-' ?></td>
                <td><?= e($row['email'] ?? '-') ?></td>
                <td><?= e($row['ip_address']) ?></td>
                <td class="danger"><?= e($row['event_type']) ?></td>
                <td><span class="small"><?= e($row['details'] ?? '-') ?></span></td>
                <td><?= e($row['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Tentativas de login recentes</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>E-mail</th>
            <th>IP</th>
            <th>Sucesso</th>
            <th>Data</th>
        </tr>
        <?php foreach ($loginAttempts as $row): ?>
            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= e($row['email'] ?? '-') ?></td>
                <td><?= e($row['ip_address']) ?></td>
                <td class="<?= !empty($row['success']) ? 'ok' : 'danger' ?>">
                    <?= !empty($row['success']) ? 'Sim' : 'Nao' ?>
                </td>
                <td><?= e($row['attempted_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Logs de auditoria recentes</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>User ID</th>
            <th>E-mail</th>
            <th>Projeto ID</th>
            <th>Acao</th>
            <th>Metadata</th>
            <th>Data</th>
        </tr>
        <?php foreach ($auditLogs as $row): ?>
            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= (int)$row['user_id'] ?></td>
                <td><?= e($row['email'] ?? '-') ?></td>
                <td><?= $row['project_id'] !== null ? (int)$row['project_id'] : '-' ?></td>
                <td><?= e($row['action_name']) ?></td>
                <td><span class="small"><?= e($row['metadata'] ?? '-') ?></span></td>
                <td><?= e($row['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Verificacoes de cartao recentes</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Projeto</th>
            <th>BIN mascarado</th>
            <th>Last4</th>
            <th>Status</th>
            <th>Origem</th>
            <th>Data</th>
        </tr>
        <?php foreach ($recentCardChecks as $row): ?>
            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= e($row['email'] ?? '-') ?></td>
                <td><?= e($row['project_name'] ?? '-') ?></td>
                <td><?= e($row['bin_masked']) ?></td>
                <td><?= e($row['last4_masked']) ?></td>
                <td><?= e($row['result_status']) ?></td>
                <td><?= e($row['source_name']) ?></td>
                <td><?= e($row['checked_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>IPs bloqueados</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>IP</th>
            <th>Motivo</th>
            <th>Bloqueado ate</th>
            <th>Criado em</th>
        </tr>
        <?php foreach ($blockedIps as $row): ?>
            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= e($row['ip_address']) ?></td>
                <td class="danger"><?= e($row['reason']) ?></td>
                <td><?= e($row['blocked_until'] ?? '-') ?></td>
                <td><?= e($row['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Mapa simplificado de ataques por pais</h2>
    <table>
        <tr>
            <th>Pais</th>
            <th>Total</th>
            <th>Distribuicao</th>
        </tr>
        <?php
        $maxCountry = 1;
        foreach ($topCountries as $countryRow) {
            if ((int)$countryRow['total'] > $maxCountry) {
                $maxCountry = (int)$countryRow['total'];
            }
        }
        ?>
        <?php foreach ($topCountries as $row): ?>
            <tr>
                <td><?= e($row['country'] ?? 'Unknown') ?></td>
                <td><?= (int)$row['total'] ?></td>
                <td>
                    <progress value="<?= (int)$row['total'] ?>" max="<?= $maxCountry ?>"></progress>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Requisicoes recentes</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>IP</th>
            <th>URI</th>
            <th>Metodo</th>
            <th>Pais</th>
            <th>User-Agent</th>
            <th>Data</th>
        </tr>
        <?php foreach ($recentRequests as $row): ?>
            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= e($row['ip_address']) ?></td>
                <td><?= e($row['request_uri']) ?></td>
                <td><?= e($row['request_method']) ?></td>
                <td><?= e($row['country'] ?? 'Unknown') ?></td>
                <td><span class="small"><?= e($row['user_agent'] ?? '-') ?></span></td>
                <td><?= e($row['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>