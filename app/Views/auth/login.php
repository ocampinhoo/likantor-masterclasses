<section class="section auth-section">
    <div class="container container--narrow">
        <h1>Iniciar sesión</h1>
        <form action="<?= url('/login') ?>" method="POST" class="form">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required value="<?= e(old('email')) ?>">
            </div>
            <div class="form-group">
                <label for="password">Contraseña</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
            </div>
            <div class="form-group form-group--checkbox">
                <label>
                    <input type="checkbox" name="remember" value="1">
                    Recordarme en este dispositivo
                </label>
            </div>
            <button type="submit" class="btn btn--primary btn--block">Entrar</button>
        </form>
        <p class="auth-links">
            <a href="<?= url('/recuperar-contrasena') ?>">¿Olvidaste tu contraseña?</a><br>
            <a href="<?= url('/registro') ?>">Crear cuenta</a>
        </p>
    </div>
</section>
