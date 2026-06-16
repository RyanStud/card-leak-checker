<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="<?= e(base_path('/public/assets/icons/favicon-32x32.png')) ?>">
    <link rel="stylesheet" href="<?= e(base_path('/public/assets/css/theme.css')) ?>">
    <title>Termos de uso</title>
</head>
<body>
    <?php require __DIR__ . '/../partials/navbar.php'; ?>
    <?php require __DIR__ . '/../partials/brand.php'; ?>

    <h1>Termos de uso</h1>
    <p><em>Última atualização: <?= e(date('d/m/Y')) ?></em></p>

    <section>
        <h2>1. Aceitação dos termos</h2>
        <p>
            Ao criar uma conta e utilizar esta plataforma, você declara que leu, entendeu e concorda com estes
            Termos de uso. Caso não concorde com qualquer condição aqui descrita, não conclua o cadastro nem
            utilize o sistema.
        </p>
    </section>

    <section>
        <h2>2. Descrição do serviço</h2>
        <p>
            A plataforma oferece a verificação de possíveis vazamentos de dados de cartão, vinculada a projetos
            aprovados, com finalidade legítima de prevenção a fraude e segurança da informação. Cada consulta
            fica registrada e associada a um projeto e a um usuário responsável.
        </p>
    </section>

    <section>
        <h2>3. Uso responsável e autorizado</h2>
        <p>Ao utilizar o sistema, você se compromete a:</p>
        <ul>
            <li>Usar a ferramenta apenas para finalidades legítimas, autorizadas e em conformidade com a lei;</li>
            <li>Não realizar consultas sem justificativa ou vínculo com um projeto aprovado;</li>
            <li>Não tentar burlar mecanismos de segurança, autenticação ou controle de acesso;</li>
            <li>Fornecer informações verdadeiras no cadastro e manter a confidencialidade das suas credenciais.</li>
        </ul>
        <p>
            O uso indevido da plataforma pode resultar na suspensão ou exclusão da conta, sem prejuízo das
            medidas legais cabíveis.
        </p>
    </section>

    <section>
        <h2>4. Completude do perfil e aprovação de projetos</h2>
        <p>
            O fornecimento de dados adicionais no seu perfil (como dados profissionais e de endereço) é
            opcional, mas contribui para tornar seu cadastro mais completo e confiável. Perfis mais completos
            transmitem maior confiança no uso da plataforma e podem favorecer a análise e a aprovação dos seus
            projetos, pois evidenciam a finalidade legítima e a responsabilidade do usuário.
        </p>
        <p>
            Você mantém total controle sobre esses dados e pode consultá-los ou removê-los a qualquer momento na
            área de <a href="<?= e(base_path('/privacy')) ?>">Privacidade e LGPD</a>.
        </p>
    </section>

    <section>
        <h2>5. Tratamento de dados pessoais (LGPD)</h2>
        <p>
            Tratamos seus dados pessoais com base nos princípios de minimização, finalidade e responsabilidade,
            em conformidade com a Lei Geral de Proteção de Dados (LGPD). Você pode consultar, gerenciar e solicitar
            a exclusão dos seus dados a qualquer momento na área de
            <a href="<?= e(base_path('/privacy')) ?>">Privacidade e LGPD</a>.
        </p>
    </section>

    <section>
        <h2>6. Responsabilidades do usuário</h2>
        <p>
            Você é responsável por toda atividade realizada na sua conta. Em caso de suspeita de uso não
            autorizado, comunique imediatamente os administradores. As consultas realizadas ficam sujeitas a
            auditoria e histórico.
        </p>
    </section>

    <section>
        <h2>7. Propriedade intelectual</h2>
        <p>
            Todo o conteúdo, marca e código desta plataforma pertencem ao Grupo Melsa e são protegidos por lei.
            É vedada a reprodução, distribuição ou modificação sem autorização prévia.
        </p>
    </section>

    <section>
        <h2>8. Limitação de responsabilidade</h2>
        <p>
            O serviço é fornecido no estado em que se encontra. Embora adotemos boas práticas de segurança,
            não garantimos disponibilidade ininterrupta nem ausência total de falhas. O Grupo Melsa não se
            responsabiliza por usos da ferramenta em desacordo com estes termos.
        </p>
    </section>

    <section>
        <h2>9. Alterações dos termos</h2>
        <p>
            Estes termos podem ser atualizados a qualquer momento para refletir melhorias no serviço ou exigências
            legais. A data da última atualização é indicada no início desta página. O uso continuado após mudanças
            implica concordância com a versão vigente.
        </p>
    </section>

    <section>
        <h2>10. Contato</h2>
        <p>
            Em caso de dúvidas sobre estes termos, entre em contato com a equipe responsável, o Grupo Melsa.
        </p>
    </section>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
