<section class="section auth-section">
    <div class="container container--narrow">
        <h1>Restablecer contraseña</h1>
        <p>Elige una nueva contraseña para tu cuenta.</p>
        <form action="<?= e(url('/restablecer-contrasena/' . $token)) ?>" method="POST" class="form">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="password">Nueva contraseña</label>
                <input type="password" id="password" name="password" required minlength="10" autocomplete="new-password">
                <span class="text-muted" style="font-size:0.85rem;">Mínimo 10 caracteres.</span>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirmar nueva contraseña</label>
                <input type="password" id="password_confirm" name="password_confirm" required minlength="10" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn--primary btn--block">Restablecer contraseña</button>
        </form>
        <p class="auth-links"><a href="<?= url('/login') ?>">Volver al login</a></p>
    </div>
</section>
