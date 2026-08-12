<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title ?? 'Admin') ?> — Likantor</title>
    <link rel="stylesheet" href="<?= asset('css/base.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <?php require __DIR__ . '/../partials/admin-sidebar.php'; ?>
        <div class="admin-content">
            <?php require __DIR__ . '/../partials/admin-header.php'; ?>

            <?php if (!empty($_SESSION['_flash'])): ?>
                <div class="flash-messages">
                    <?php foreach (['success', 'error'] as $type): ?>
                        <?php if ($msg = ($_SESSION['_flash'][$type] ?? null)): ?>
                            <div class="flash flash--<?= e($type) ?>"><?= e($msg) ?></div>
                            <?php unset($_SESSION['_flash'][$type]); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <main class="admin-main">
                <?= $content ?? '' ?>
            </main>
        </div>
    </div>
    <script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
