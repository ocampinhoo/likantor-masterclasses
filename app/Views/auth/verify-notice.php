<section class="section auth-section">
    <div class="container container--narrow text-center">
        <h1>Revisa tu correo</h1>
        <p class="section__lead" style="margin-inline:auto;">
            Te enviamos un enlace para verificar tu cuenta. Revisa tu bandeja de entrada
            (y la carpeta de spam o promociones, por si acaso). El enlace expira en unas horas.
        </p>

        <form action="<?= url('/verificar-email/reenviar') ?>" method="POST" class="form" style="max-width:360px;margin:2rem auto 0;">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="resend-email">¿No recibiste el correo? Reenviar a:</label>
                <input type="email" id="resend-email" name="email" required maxlength="255" autocomplete="email">
            </div>
            <button type="submit" class="btn btn--secondary btn--block">Reenviar correo de verificación</button>
        </form>

        <p class="auth-links" style="margin-top:1.5rem;">
            <a href="<?= url('/login') ?>">Volver al inicio de sesión</a>
        </p>
    </div>
</section>
