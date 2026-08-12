<?php

use App\Services\AuthService;

$auth = new AuthService();
$currentUser = $auth->user();
$isLanding = ($meta['header_variant'] ?? '') === 'landing';
$headerClass = 'site-header' . ($isLanding ? ' site-header--landing' : '');
$landingUrl = url('/masterclass/revision-estructuras-post-sismo');
?>
<header class="<?= e($headerClass) ?>">
    <div class="container header-inner">
        <a href="<?= url('/') ?>" class="logo" aria-label="LIKANTOR — Inicio">
            <span class="logo__brand">LIKANTOR</span>
            <span class="logo__tagline">Ingeniería en Estructuras</span>
        </a>

        <button type="button" class="nav-toggle" aria-expanded="false" aria-controls="main-nav" aria-label="Abrir menú de navegación">
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
            <span class="nav-toggle__bar"></span>
        </button>

        <nav id="main-nav" class="nav" aria-label="Principal">
            <a href="<?= url('/') ?>"<?= is_active_path('/') && !str_contains(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', 'masterclass') ? ' class="is-active" aria-current="page"' : '' ?>>Inicio</a>
            <a href="<?= e($landingUrl) ?>"<?= str_contains(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '', '/masterclass/') ? ' class="is-active" aria-current="page"' : '' ?>>Masterclass</a>
            <?php if ($currentUser): ?>
                <a href="<?= url('/mi-cuenta') ?>"<?= is_active_path('/mi-cuenta') ? ' class="is-active"' : '' ?>>Mi cuenta</a>
                <?php if ($auth->isAdmin()): ?>
                    <a href="<?= url('/admin') ?>">Admin</a>
                <?php endif; ?>
                <form action="<?= url('/logout') ?>" method="POST" class="nav-logout">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn--ghost btn--sm">Salir</button>
                </form>
            <?php else: ?>
                <a href="<?= url('/login') ?>">Iniciar sesión</a>
                <a href="<?= e($landingUrl) ?>#inscribirse" class="btn btn--primary btn--sm">Inscribirme</a>
            <?php endif; ?>
        </nav>
    </div>
</header>
