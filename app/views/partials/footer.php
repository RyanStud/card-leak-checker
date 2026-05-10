<?php
$footerYear = date('Y');
$footerDate = date('d/m/Y');
?>
<footer class="site-footer" aria-label="Rodape do site">
    <p class="site-footer__text">Data: <?= e($footerDate) ?> | &copy; <?= e((string)$footerYear) ?> Grupo Melsa. Todos os direitos reservados.</p>
</footer>

<?php if (!cookie_consent_exists()): ?>
    <?php require __DIR__ . '/cookie-consent.php'; ?>
<?php endif; ?>

<script src="<?= e(base_path('/public/js/banner-toggle.js')) ?>" defer></script>
