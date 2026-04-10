<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Verificar cartão</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>
    <h1>Verificação de possível vazamento</h1>

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

    <section class="cards-info-box">
        <h3 class="cards-info-box__title">Por que é necessário criar e aprovar um projeto?</h3>
        <p class="cards-info-box__intro">A consulta de cartão exige um projeto para garantir uso responsável e rastreável da plataforma.</p>
        <ul class="cards-info-box__list">
            <li>Cada consulta fica vinculada a um objetivo formal (ex.: prevenção a fraude em um sistema específico).</li>
            <li>A aprovação do projeto impede uso indevido da ferramenta para consultas sem justificativa.</li>
            <li>O vínculo com projeto permite auditoria e histórico de quem consultou, quando e para qual finalidade.</li>
        </ul>
    </section>

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
                        <?php
                            $status = $project['approval_status'] ?? 'pending';
                            $statusText = '';
                            $disabled = false;

                            if ($status === 'approved') {
                                $statusText = ' ✓ Aprovado';
                            } elseif ($status === 'pending') {
                                $statusText = ' ⏳ Pendente';
                                $disabled = true;
                            } elseif ($status === 'rejected') {
                                $statusText = ' ✗ Rejeitado';
                                $disabled = true;
                            }
                        ?>
                        <option value="<?= (int)$project['id'] ?>" <?= $disabled ? 'disabled' : '' ?>>
                            <?= e($project['name']) ?> (<?= e($project['privacy_mode']) ?>)<?= $statusText ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label>Número do cartão</label><br>
                <input type="text" name="card_number" placeholder="Ex: 4111111111111111" maxlength="19" required>
            </div>

            <div>
                <label>Mês de validade (MM)</label><br>
                <input type="text" name="expiry_month" placeholder="Ex: 08" maxlength="2" required>
            </div>

            <div>
                <label>Ano de validade (AAAA)</label><br>
                <input type="text" name="expiry_year" placeholder="Ex: 2029" maxlength="4" required>
            </div>

            <div>
                <label>CVV</label><br>
                <input type="password" name="cvv" placeholder="Ex: 123" maxlength="4" required>
            </div>

            <button type="submit">Verificar</button>
        </form>
    <?php endif; ?>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
