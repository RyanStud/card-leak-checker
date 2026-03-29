<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Sobre nos</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>

    <h1>Sobre nos</h1>

    <section>
        <h2>Grupo de desenvolvimento</h2>
        <p><strong>Grupo Melsa</strong></p>
        <p>
            Somos o Grupo Melsa, equipe responsavel pela idealizacao e desenvolvimento deste sistema.
            Nosso objetivo e entregar uma plataforma confiavel para verificacao de possiveis vazamentos,
            com foco em seguranca, privacidade e usabilidade.
        </p>
    </section>

    <section>
        <h2>Seguranca e privacidade</h2>
        <p>
            Todos os dados sensiveis sao tratados com controles de seguranca e boas praticas de protecao.
            Utilizamos criptografia para proteger informacoes criticas, mecanismos de autenticacao forte,
            validacoes de entrada e monitoramento de atividades suspeitas.
        </p>
        <p>
            Tambem seguimos principios de minimizacao e responsabilidade no tratamento de dados,
            alinhados com a LGPD e com o compromisso de transparencia com os usuarios.
        </p>
    </section>

    <section>
        <h2>Compromisso</h2>
        <p>
            O Grupo Melsa trabalha continuamente para evoluir o sistema, reforcar a seguranca e
            melhorar a experiencia dos usuarios em cada nova versao.
        </p>
    </section>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
