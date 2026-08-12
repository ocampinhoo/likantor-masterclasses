<section class="section" style="min-height:55vh;display:flex;align-items:center;">
    <div class="container container--narrow text-center">
        <span class="section__eyebrow">Solicitud recibida</span>
        <h1 class="section__title">¡Gracias! Revisa tu correo</h1>

        <?php if (!empty($masterclassName)): ?>
            <p class="section__lead" style="margin-inline:auto;">
                Te enviamos la información sobre <strong><?= e($masterclassName) ?></strong> a tu correo electrónico.
                Si no la ves en unos minutos, revisa tu carpeta de spam o promociones.
            </p>
        <?php else: ?>
            <p class="section__lead" style="margin-inline:auto;">
                Te enviamos la información a tu correo electrónico. Si no la ves en unos minutos,
                revisa tu carpeta de spam o promociones.
            </p>
        <?php endif; ?>

        <div class="hero-landing__cta" style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
            <a href="<?= e($masterclassUrl) ?>" class="btn btn--primary btn--lg">Ver la Masterclass</a>
            <a href="<?= url('/') ?>" class="btn btn--secondary">Volver al inicio</a>
        </div>
    </div>
</section>
