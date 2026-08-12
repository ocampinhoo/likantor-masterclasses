<!DOCTYPE html>
<html lang="es-MX">
<head>
    <?php require __DIR__ . '/../partials/meta.php'; ?>
</head>
<body class="<?= e($bodyClass ?? ($meta['body_class'] ?? '')) ?>">
    <a href="#main-content" class="sr-only skip-link">Saltar al contenido principal</a>
    <?php require __DIR__ . '/../partials/header.php'; ?>

    <?php if (!empty($_SESSION['_flash'])): ?>
        <div class="container flash-messages" role="alert" aria-live="polite">
            <?php foreach (['success', 'error'] as $type): ?>
                <?php if ($msg = ($_SESSION['_flash'][$type] ?? null)): ?>
                    <div class="flash flash--<?= e($type) ?>"><?= e($msg) ?></div>
                    <?php unset($_SESSION['_flash'][$type]); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <main id="main-content">
        <?= $content ?? '' ?>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>

    <?php if (!empty($meta['sticky_cta'])): ?>
        <div class="sticky-cta" aria-hidden="false">
            <a href="<?= e($meta['sticky_cta_url'] ?? '#inscribirse') ?>" class="btn btn--primary btn--block btn--lg">
                <?= e($meta['sticky_cta_text'] ?? 'Reservar mi lugar') ?>
            </a>
        </div>
    <?php endif; ?>

    <script src="<?= asset('js/main.js') ?>" defer></script>
    <?php foreach ($meta['extra_js'] ?? [] as $jsFile): ?>
    <script src="<?= asset('js/' . $jsFile) ?>" defer></script>
    <?php endforeach; ?>
    <?php if (($meta['load_timezone'] ?? false) === true): ?>
    <script src="<?= asset('js/timezone.js') ?>" defer></script>
    <?php endif; ?>
    <?php if (!empty($meta['json_ld'])): ?>
    <script type="application/ld+json"><?= $meta['json_ld'] ?></script>
    <?php endif; ?>
</body>
</html>
