<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Projetos</title>
</head>
<body>
    <h1>Projetos</h1>

    <p>
        <a href="/card-leak-checker/public/dashboard">Dashboard</a> |
        <a href="/card-leak-checker/public/check-card">Verificar cartão</a> |
        <a href="/card-leak-checker/public/cards/history">Histórico</a>
    </p>

    <?php if ($msg = flash('error')): ?>
        <p style="color:red;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <?php if ($msg = flash('success')): ?>
        <p style="color:green;"><?= htmlspecialchars($msg) ?></p>
    <?php endif; ?>

    <h2>Criar novo projeto</h2>

    <form method="POST" action="/card-leak-checker/public/projects">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">

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
                <th>Criado em</th>
            </tr>
            <?php foreach ($projects as $project): ?>
                <tr>
                    <td><?= (int)$project['id'] ?></td>
                    <td><?= htmlspecialchars($project['name']) ?></td>
                    <td><?= htmlspecialchars($project['slug']) ?></td>
                    <td><?= htmlspecialchars($project['privacy_mode']) ?></td>
                    <td><?= htmlspecialchars($project['role']) ?></td>
                    <td><?= htmlspecialchars($project['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>