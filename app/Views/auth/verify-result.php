<section class="section auth-section">
    <div class="container container--narrow text-center">
        <?php if ($success): ?>
            <h1>¡Correo verificado!</h1>
            <p class="section__lead" style="margin-inline:auto;">
                Tu cuenta ha sido verificada correctamente. Ya puedes acceder a tu área privada.
            </p>
            <a href="<?= url('/mi-cuenta') ?>" class="btn btn--primary btn--lg">Ir a mi cuenta</a>
        <?php else: ?>
            <h1>Enlace inválido</h1>
            <p class="section__lead" style="margin-inline:auto;">
                <?= e($message ?? 'Este enlace de verificación no es válido o ya expiró.') ?>
            </p>

            <form action="<?= url('/verificar-email/reenviar') ?>" method="POST" class="form" style="max-width:360px;margin:2rem auto 0;">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="resend-email">Solicitar un nuevo enlace para:</label>
                    <input type="email" id="resend-email" name="email" required maxlength="255" autocomplete="email">
                </div>
                <button type="submit" class="btn btn--secondary btn--block">Reenviar correo de verificación</button>
            </form>
        <?php endif; ?>
    </div>
</section>
