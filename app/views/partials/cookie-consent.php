<?php
$requestPath = current_path();
?>
<section class="cookie-consent" aria-label="Consentimento de cookies">
    <div style="flex:1;">
        <h2 class="cookie-consent__title">Cookies e privacidade</h2>
        <p class="cookie-consent__intro">Usamos cookies essenciais para manter seguranca, sessao e autenticacao. Com sua permissao, tambem podemos ativar cookies opcionais de metricas.</p>

        <ul class="cookie-consent__list" aria-label="Lista de cookies coletados">
            <li><strong><?= e(session_name()) ?></strong>: essencial para manter sua sessao ativa, proteger o login e reduzir acessos indevidos.</li>
            <li><strong>lgpd_cookie_consent</strong>: essencial para salvar sua escolha de consentimento (somente essenciais ou todos).</li>
            <li><strong>Cookies de metricas</strong>: opcionais, usados apenas se voce escolher "Aceitar todos", com foco em melhoria de usabilidade e desempenho.</li>
        </ul>
    </div>

    <div class="cookie-consent__actions">
        <form method="POST" action="<?= e(base_path('/privacy/cookies-consent')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="mode" value="essential">
            <input type="hidden" name="redirect_to" value="<?= e($requestPath) ?>">
            <button type="submit" class="cookie-consent__button cookie-consent__button--secondary">Aceitar somente essenciais</button>
        </form>

        <form method="POST" action="<?= e(base_path('/privacy/cookies-consent')) ?>">
            <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="mode" value="all">
            <input type="hidden" name="redirect_to" value="<?= e($requestPath) ?>">
            <button type="submit" class="cookie-consent__button">Aceitar todos</button>
        </form>
    </div>
</section>
