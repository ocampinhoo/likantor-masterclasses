<?php

use App\Services\AuthService;

$user = (new AuthService())->user();
?>
<header class="admin-header">
    <h1 class="admin-header__title"><?= e($title ?? 'Admin') ?></h1>
    <?php if ($user): ?>
        <span class="admin-header__user"><?= e($user['name']) ?> (<?= e($user['role']) ?>)</span>
    <?php endif; ?>
</header>
