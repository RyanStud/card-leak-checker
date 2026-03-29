<?php
$footerYear = date('Y');
$footerDate = date('d/m/Y');
?>
<footer class="site-footer" aria-label="Rodape do site">
    <p class="site-footer__text">Data: <?= e($footerDate) ?> | &copy; <?= e((string)$footerYear) ?> Grupo Melsa. Todos os direitos reservados.</p>
</footer>
