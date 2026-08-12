<section class="section">
    <div class="container">
        <h1>Hola, <?= e($user['name'] ?? '') ?></h1>
        <p class="section-intro">Bienvenido a tu área privada.</p>

        <?php if (empty($user['email_verified_at'])): ?>
            <div class="flash flash--error" style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                <span>Tu correo aún no está verificado. Revisa tu bandeja de entrada.</span>
                <form action="<?= url('/verificar-email/reenviar') ?>" method="POST" style="margin:0;">
                    <?= csrf_field() ?>
                    <input type="hidden" name="email" value="<?= e($user['email'] ?? '') ?>">
                    <button type="submit" class="btn btn--secondary btn--sm">Reenviar enlace</button>
                </form>
            </div>
        <?php endif; ?>

        <div class="dashboard-actions">
            <a href="<?= url('/mi-cuenta/perfil') ?>" class="btn btn--secondary">Editar perfil</a>
            <a href="<?= url('/masterclasses') ?>" class="btn btn--primary">Ver Masterclasses</a>
        </div>

        <h2>Mis Masterclasses</h2>

        <?php if (empty($cards)): ?>
            <p class="text-muted">Aún no hay Masterclasses disponibles. <a href="<?= url('/masterclasses') ?>">Explora el catálogo</a>.</p>
        <?php else: ?>
            <div class="account-masterclasses">
                <?php foreach ($cards as $card): ?>
                    <?php
                    $state = (string) ($card['access_state'] ?? 'none');
                    ?>
                    <article class="account-mc">
                        <header class="account-mc__header">
                            <h3 class="account-mc__title"><?= e($card['name'] ?? '') ?></h3>
                            <span class="badge badge--<?= e($state === 'paid' ? 'paid' : ($state === 'pending' ? 'pending' : 'unknown')) ?>">
                                <?= e($card['status_label'] ?? '') ?>
                            </span>
                        </header>

                        <p class="account-mc__headline"><?= e($card['headline'] ?? '') ?></p>

                        <?php if (!empty($card['event_date']) || !empty($card['event_time']) || !empty($card['duration_minutes'])): ?>
                            <ul class="account-mc__meta">
                                <?php if (!empty($card['event_date'])): ?>
                                    <li><strong>Fecha:</strong> <?= e($card['event_date']) ?></li>
                                <?php endif; ?>
                                <?php if (!empty($card['event_time'])): ?>
                                    <li><strong>Hora:</strong> <?= e($card['event_time']) ?></li>
                                <?php endif; ?>
                                <?php if (!empty($card['duration_minutes'])): ?>
                                    <li><strong>Duración:</strong> <?= (int) $card['duration_minutes'] ?> min</li>
                                <?php endif; ?>
                            </ul>
                        <?php endif; ?>

                        <div class="account-mc__actions">
                            <?php if ($state === 'paid'): ?>
                                <?php if (!empty($card['zoom_ready'])): ?>
                                    <a
                                        class="btn btn--primary"
                                        href="<?= url('/mi-cuenta/masterclasses/' . rawurlencode((string) $card['slug']) . '/acceso-zoom') ?>"
                                    >
                                        Entrar a la Masterclass por Zoom
                                    </a>
                                <?php else: ?>
                                    <p class="text-muted">Tu acceso está confirmado. El enlace de Zoom se publicará aquí antes del evento.</p>
                                <?php endif; ?>
                            <?php elseif ($state === 'pending'): ?>
                                <p class="text-muted">Te avisaremos cuando el pago se confirme. No es necesario volver a pagar.</p>
                            <?php elseif ($state === 'none'): ?>
                                <a class="btn btn--primary" href="<?= url('/checkout/' . rawurlencode((string) $card['slug'])) ?>">
                                    Reserva tu lugar
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
