<?php
$requestPath = parse_url((string)($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';

$isAdmin = false;
if (is_logged_in()) {
    if (isset($user['role'])) {
        $isAdmin = (($user['role'] ?? 'user') === 'admin');
    } elseif (!empty($_SESSION['user_id'])) {
        $userModel = new User();
        $sessionUser = $userModel->findById((int)$_SESSION['user_id']);
        $isAdmin = (($sessionUser['role'] ?? 'user') === 'admin');
    }
}

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
        $links[] = ['/projects/approval', 'Aprovacoes'];
        $links[] = ['/projects/approval-history', 'Hist. aprovacoes'];
    }
} else {
    $links = [
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