<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Privacidade e LGPD</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Privacidade e LGPD</h1>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= e($msg) ?></p>
    <?php endif; ?>

    <h2>Dados do usuário no sistema</h2>

    <?php if (!empty($profile)): ?>
        <?php
            $labels = [
                'id' => 'ID',
                'name' => 'Nome',
                'email' => 'E-mail',
                'cpf' => 'CPF',
                'job_title' => 'Trabalho',
                'cep' => 'CEP',
                'address_street' => 'Logradouro',
                'address_number' => 'Numero',
                'address_complement' => 'Complemento',
                'address_neighborhood' => 'Bairro',
                'address_city' => 'Cidade',
                'address_state' => 'UF',
                'role' => 'Papel',
                'email_verified' => 'E-mail verificado',
                'two_factor_enabled' => '2FA ativo',
                'two_factor_secret' => 'Segredo 2FA',
                'password_hash' => 'Hash da senha',
                'created_at' => 'Criado em',
                'updated_at' => 'Atualizado em',
            ];

            $sensitiveFields = ['password_hash', 'two_factor_secret'];
        ?>

        <table>
            <tr>
                <th>Campo</th>
                <th>Valor armazenado</th>
            </tr>
            <?php foreach ($profile as $field => $rawValue): ?>
                <?php
                    $displayLabel = $labels[$field] ?? strtoupper(str_replace('_', ' ', $field));

                    if (in_array($field, $sensitiveFields, true)) {
                        $displayValue = !empty($rawValue) ? 'definido (valor oculto)' : 'nao definido';
                    } elseif ($field === 'email_verified' || $field === 'two_factor_enabled') {
                        $displayValue = !empty($rawValue) ? 'Sim' : 'Nao';
                    } else {
                        $displayValue = ($rawValue === null || $rawValue === '') ? 'nao informado' : (string)$rawValue;
                    }
                ?>
                <tr>
                    <td><strong><?= e($displayLabel) ?></strong></td>
                    <td><?= e($displayValue) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h2>Resumo de dados</h2>
    <ul>
        <li><strong>Total de verificações:</strong> <?= (int)$historyCount ?></li>
        <li><strong>Total de projetos próprios:</strong> <?= (int)$projectsCount ?></li>
    </ul>

    <hr>

    <h2>Exclusão parcial de dados</h2>

    <h3>1. Apagar histórico de verificações</h3>
    <p>Remove todos os registros da tabela de verificações vinculados ao seu usuário.</p>

    <form method="POST" action="<?= e(base_path('/privacy/delete-history')) ?>"
          onsubmit="return confirm('Deseja realmente apagar seu histórico de verificações?');">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit">Apagar histórico</button>
    </form>

    <br>

    <h3>2. Apagar projetos próprios</h3>
    <p>Remove os projetos que você criou. Isso pode apagar também dados relacionados por cascade.</p>

    <form method="POST" action="<?= e(base_path('/privacy/delete-projects')) ?>"
          onsubmit="return confirm('Deseja realmente apagar todos os seus projetos próprios?');">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
        <button type="submit">Apagar projetos próprios</button>
    </form>

    <hr>

    <h2>Exclusão total da conta</h2>
    <p>
        Esta ação exclui sua conta e dados relacionados.
        Para maior segurança, confirme com sua senha e com o código do Google Authenticator.
    </p>

    <form method="POST" action="<?= e(base_path('/privacy/delete-account')) ?>"
          onsubmit="return confirm('Tem certeza que deseja excluir sua conta definitivamente?');">
        <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

        <div>
            <label>Confirme sua senha</label><br>
            <input type="password" name="password" required>
        </div>

        <div>
            <label>Código do Google Authenticator</label><br>
            <input type="text" name="code" maxlength="6" required>
        </div>

        <br>
        <button type="submit" style="color:red;">Excluir conta definitivamente</button>
    </form>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>