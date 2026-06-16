<?php
$footerYear = date('Y');
$footerDate = date('d/m/Y');
?>
<footer class="site-footer" aria-label="Rodape do site">
    <p class="site-footer__text">
        Data: <?= e($footerDate) ?>
        <span class="site-footer__sep" aria-hidden="true">·</span>
        &copy; <?= e((string)$footerYear) ?> Grupo Melsa. Todos os direitos reservados.
        <span class="site-footer__sep" aria-hidden="true">·</span>
        <a class="site-footer__link" href="<?= e(base_path('/terms')) ?>">Termos de uso</a>
    </p>
</footer>

<?php if (!cookie_consent_exists()): ?>
    <?php require __DIR__ . '/cookie-consent.php'; ?>
<?php endif; ?>

<script src="<?= e(base_path('/public/js/banner-toggle.js')) ?>" defer></script>
<script src="<?= e(base_path('/public/js/form-confirmations.js')) ?>" defer></script>
