<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verificar cartão</title>
</head>
<body>
    <h1>Verificação de possível vazamento</h1>

    <p>
        <a href="<?= e(base_path('/dashboard')) ?>">Dashboard</a> |
        <a href="<?= e(base_path('/projects')) ?>">Projetos</a> |
        <a href="<?= e(base_path('/cards/history')) ?>">Histórico</a>
    </p>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= e($msg) ?></p>
    <?php endif; ?>

    <?php if (!empty($_SESSION['check_result'])): ?>
        <?php $result = $_SESSION['check_result']; unset($_SESSION['check_result']); ?>
        <div style="border:1px solid #333; padding:12px; margin-bottom:20px;">
            <h3>Resultado da última consulta</h3>
            <p><strong>BIN mascarado:</strong> <?= e($result['bin_masked']) ?></p>
            <p><strong>Últimos 4:</strong> <?= e($result['last4_masked']) ?></p>
            <p><strong>Status:</strong> <?= e($result['result_status']) ?></p>
            <p><strong>Data:</strong> <?= e($result['checked_at']) ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($projects)): ?>
        <p>Você precisa criar um projeto antes de consultar.</p>
    <?php else: ?>
        <form method="POST" action="<?= e(base_path('/check-card')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

            <div>
                <label>Projeto</label><br>
                <select name="project_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= (int)$project['id'] ?>">
                            <?= e($project['name']) ?> (<?= e($project['privacy_mode']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Número do cartão (apenas para demonstração)</label><br>
                <input type="text" name="card_number" placeholder="Ex: 4111111111111111" required>
            </div>

            <button type="submit">Verificar</button>
        </form>

        <p style="margin-top:16px;">
            Para teste rápido, finais <strong>1111</strong>, <strong>1234</strong>, <strong>9999</strong> e <strong>0000</strong>
            retornam “possible_leak_found”.
        </p>
    <?php endif; ?>
</body>
</html>