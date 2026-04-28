<?php
$requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

$isAdmin = is_logged_in() && can_view_admin_area();

$links = [];

if (is_logged_in()) {
    $links = [
        ['/dashboard', 'Dashboard'],
        ['/projects', 'Projetos'],
        ['/check-card', 'Verificar cartao'],
        ['/cards/history', 'Historico'],
        ['/privacy', 'Privacidade'],
    ];

    if ($isAdmin) {
        $links[] = ['/admin', 'Admin'];
        $links[] = ['/admin/users', 'Usuarios'];
        $links[] = ['/projects/approval', 'Aprovacoes'];
        $links[] = ['/projects/approval-history', 'Hist. aprovacoes'];
    }
} else {
    $links = [
        ['/about', 'Sobre nos'],
        ['/login', 'Login'],
        ['/register', 'Cadastro'],
        ['/forgot-password', 'Recuperar senha'],
    ];
}
?>

<header class="site-header">
    <div class="site-header__inner">
        <nav class="site-header__nav" aria-label="Navegacao principal">
            <?php foreach ($links as [$href, $label]): ?>
                <?php $isActive = $requestPath === $href; ?>
                <a class="site-header__link<?= $isActive ? ' is-active' : '' ?>" href="<?= e(base_path($href)) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if (is_logged_in()): ?>
            <form class="site-header__logout" method="POST" action="<?= e(base_path('/logout')) ?>">
                <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
                <button type="submit">Sair</button>
            </form>
        <?php endif; ?>
    </div>
</header>