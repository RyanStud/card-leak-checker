<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verificar cartão</title>
</head>
<body>
    <h1>Verificação de possível vazamento</h1>

    <p>
        <a href="/card-leak-checker/public/dashboard">Dashboard</a> |
        <a href="/card-leak-checker/public/projects">Projetos</a> |
        <a href="/card-leak-checker/public/cards/history">Histórico</a>
    </p>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if (!empty($_SESSION['check_result'])): ?>
        <?php $result = $_SESSION['check_result']; unset($_SESSION['check_result']); ?>
        <div style="border:1px solid #333; padding:12px; margin-bottom:20px;">
            <h3>Resultado da última consulta</h3>
            <p><strong>BIN mascarado:</strong> <?= htmlspecialchars($result['bin_masked']) ?></p>
            <p><strong>Últimos 4:</strong> <?= htmlspecialchars($result['last4_masked']) ?></p>
            <p><strong>Status:</strong> <?= htmlspecialchars($result['result_status']) ?></p>
            <p><strong>Data:</strong> <?= htmlspecialchars($result['checked_at']) ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($projects)): ?>
        <p>Você precisa criar um projeto antes de consultar.</p>
    <?php else: ?>
        <form method="POST" action="/card-leak-checker/public/check-card">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

            <div>
                <label>Projeto</label><br>
                <select name="project_id" required>
                    <option value="">Selecione</option>
                    <?php foreach ($projects as $project): ?>
                        <option value="<?= (int)$project['id'] ?>">
                            <?= htmlspecialchars($project['name']) ?> (<?= htmlspecialchars($project['privacy_mode']) ?>)
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