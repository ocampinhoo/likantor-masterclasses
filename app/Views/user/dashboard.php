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
        <?php if (empty($enrollments)): ?>
            <p class="text-muted">Aún no tienes inscripciones. <a href="<?= url('/masterclasses') ?>">Explora las Masterclasses disponibles</a>.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Masterclass</th>
                            <th>Estado</th>
                            <th>Acceso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td><?= e($enrollment['masterclass_name']) ?></td>
                                <td><span class="badge badge--<?= e($enrollment['status']) ?>"><?= e($enrollment['status']) ?></span></td>
                                <td>
                                    <?php if ($enrollment['status'] === 'paid'): ?>
                                        <span class="text-success">Acceso activo</span>
                                    <?php else: ?>
                                        <span class="text-muted">Sin acceso</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
