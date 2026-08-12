<section class="section auth-section">
    <div class="container container--narrow">
        <h1>Crear cuenta</h1>
        <form action="<?= url('/registro') ?>" method="POST" class="form">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="name">Nombre completo</label>
                <input type="text" id="name" name="name" required maxlength="150" value="<?= e(old('name')) ?>">
            </div>
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required maxlength="255" value="<?= e(old('email')) ?>">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required minlength="10" autocomplete="new-password">
                <span class="text-muted" style="font-size:0.85rem;">Mínimo 10 caracteres.</span>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmar contraseña</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="10" autocomplete="new-password">
            </div>
            <div class="form-group form-group--checkbox">
                <label>
                    <input type="checkbox" name="privacy" value="1" required>
                    Acepto el <a href="<?= url('/aviso-de-privacidad') ?>" target="_blank" rel="noopener">Aviso de Privacidad</a>
                    y los <a href="<?= url('/terminos-y-condiciones') ?>" target="_blank" rel="noopener">Términos y Condiciones</a>.
                </label>
            </div>
            <button type="submit" class="btn btn--primary btn--block">Registrarme</button>
        </form>
        <p class="auth-links">
            <a href="<?= url('/login') ?>">¿Ya tienes cuenta? Inicia sesión</a>
        </p>
    </div>
</section>
