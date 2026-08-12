<section class="section auth-section">
    <div class="container container--narrow">
        <h1>Recuperar contraseña</h1>
        <p>Ingresa tu correo y te enviaremos instrucciones para restablecer tu contraseña.</p>
        <form action="<?= url('/recuperar-contrasena') ?>" method="POST" class="form">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required>
            </div>
            <button type="submit" class="btn btn--primary btn--block">Enviar instrucciones</button>
        </form>
        <p class="auth-links"><a href="<?= url('/login') ?>">Volver al login</a></p>
    </div>
</section>
