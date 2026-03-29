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

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <p>Bem-vindo, <strong><?= e($user['name'] ?? 'Usuário') ?></strong></p>
    <p>E-mail: <?= e($user['email'] ?? '') ?></p>
    <p>Papel: <?= e($user['role'] ?? 'user') ?></p>
    <p>2FA ativo: <?= !empty($user['two_factor_enabled']) ? 'Sim' : 'Não' ?></p>

    <h2>Meu perfil</h2>

    <!-- Modo Visualização -->
    <div id="profile-view" style="display: block;">
        <p><strong>Nome:</strong> <?= e($user['name'] ?? '-') ?></p>
        <p><strong>CPF:</strong> <?= e($user['cpf'] ?? '-') ?></p>
        <p><strong>Trabalho:</strong> <?= e($user['job_title'] ?? '-') ?></p>
        <p><strong>CEP:</strong> <?= e($user['cep'] ?? '-') ?></p>
        <p><strong>Logradouro:</strong> <?= e($user['address_street'] ?? '-') ?></p>
        <p><strong>Número:</strong> <?= e($user['address_number'] ?? '-') ?></p>
        <p><strong>Complemento:</strong> <?= e($user['address_complement'] ?? '-') ?></p>
        <p><strong>Bairro:</strong> <?= e($user['address_neighborhood'] ?? '-') ?></p>
        <p><strong>Cidade:</strong> <?= e($user['address_city'] ?? '-') ?></p>
        <p><strong>UF:</strong> <?= e($user['address_state'] ?? '-') ?></p>
        <button type="button" id="edit-profile-btn">Editar Perfil</button>
    </div>

    <!-- Modo Edição -->
    <div id="profile-edit" style="display: none;">
    <form method="POST" action="<?= e(base_path('/dashboard/profile')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
            <label>Nome</label><br>
            <input type="text" name="name" maxlength="100" value="<?= e($user['name'] ?? '') ?>" required>
        </div>

        <div>
            <label>CPF</label><br>
            <input type="text" id="cpf" name="cpf" maxlength="14" placeholder="000.000.000-00" value="<?= e($user['cpf'] ?? '') ?>">
        </div>

        <div>
            <label>Trabalho</label><br>
            <input type="text" name="job_title" maxlength="120" placeholder="Ex: Analista de Segurança" value="<?= e($user['job_title'] ?? '') ?>">
        </div>

        <h3>Endereço</h3>

        <div>
            <label>CEP</label><br>
            <input type="text" id="cep" name="cep" maxlength="9" placeholder="00000-000" value="<?= e($user['cep'] ?? '') ?>">
            <small id="cep_status" class="field-hint">Digite o CEP para preencher logradouro, bairro, cidade e UF automaticamente.</small>
        </div>

        <div>
            <label>Logradouro</label><br>
            <input type="text" id="address_street" name="address_street" maxlength="150" value="<?= e($user['address_street'] ?? '') ?>">
        </div>

        <div>
            <label>Número</label><br>
            <input type="text" name="address_number" maxlength="20" value="<?= e($user['address_number'] ?? '') ?>">
        </div>

        <div>
            <label>Complemento</label><br>
            <input type="text" name="address_complement" maxlength="120" value="<?= e($user['address_complement'] ?? '') ?>">
        </div>

        <div>
            <label>Bairro</label><br>
            <input type="text" id="address_neighborhood" name="address_neighborhood" maxlength="120" value="<?= e($user['address_neighborhood'] ?? '') ?>">
        </div>

        <div>
            <label>Cidade</label><br>
            <input type="text" id="address_city" name="address_city" maxlength="120" value="<?= e($user['address_city'] ?? '') ?>">
        </div>

        <div>
            <label>UF</label><br>
            <input type="text" id="address_state" name="address_state" maxlength="2" placeholder="SP" value="<?= e($user['address_state'] ?? '') ?>">
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit">Salvar perfil</button>
            <button type="button" id="cancel-profile-btn">Cancelar</button>
        </div>
    </form>
    </div>

    <h2>Alterar senha</h2>

    <!-- Modo Visualização -->
    <div id="password-view" style="display: block;">
        <p>Clique no botão abaixo para alterar sua senha.</p>
        <button type="button" id="edit-password-btn">Alterar Senha</button>
    </div>

    <!-- Modo Edição -->
    <div id="password-edit" style="display: none;">
    <form method="POST" action="<?= e(base_path('/dashboard/password')) ?>">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
            <label>Senha atual</label><br>
            <input type="password" name="current_password" required>
        </div>

        <div>
            <label>Nova senha</label><br>
            <input type="password" name="new_password" required>
        </div>

        <div>
            <label>Confirmar nova senha</label><br>
            <input type="password" name="new_password_confirmation" required>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit">Alterar senha</button>
            <button type="button" id="cancel-password-btn">Cancelar</button>
        </div>
    </form>
    </div>

    <?php $dashboardScriptVersion = (string) (@filemtime(__DIR__ . '/../../../public/js/dashboard-profile.js') ?: time()); ?>
    <script src="<?= e(base_path('/public/js/dashboard-profile.js?v=' . $dashboardScriptVersion)) ?>"></script>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>