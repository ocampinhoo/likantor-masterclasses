<?php

use App\Services\AuthService;

$mc = $masterclass;
$eventUtc = $mc['event_starts_at'];
$eventTz = $mc['timezone'];
$isLoggedIn = (new AuthService())->check();
$enrollUrl = $isLoggedIn ? url('/checkout/' . $mc['slug']) : url('/registro');
?>

<!-- Hero -->
<section class="hero hero--landing">
    <div class="container">
        <p class="hero__eyebrow"><?= e($mc['instructor_name']) ?> · Likantor</p>
        <h1><?= e($mc['name']) ?></h1>
        <?php if (!empty($mc['subtitle'])): ?>
            <p class="hero__lead"><?= e($mc['subtitle']) ?></p>
        <?php endif; ?>
        <div class="event-datetime" id="event-datetime"
             data-event-utc="<?= e($eventUtc) ?>"
             data-event-timezone="<?= e($eventTz) ?>">
            <p><strong>Fecha y hora (CDMX):</strong> <span class="event-datetime__cdmx">—</span></p>
            <p><strong>Tu hora local:</strong> <span class="event-datetime__local">—</span></p>
        </div>
        <p class="hero__meta"><?= (int) $mc['duration_minutes'] ?> min · Online · Zoom · <?= e(number_format((float) $mc['price'], 2)) ?> <?= e($mc['currency']) ?></p>
        <a href="<?= $isLoggedIn ? e($enrollUrl) : '#inscribirse' ?>" class="btn btn--primary btn--lg">Inscribirme</a>
    </div>
</section>

<!-- Problema -->
<section class="section">
    <div class="container container--narrow">
        <h2>Después de un sismo, ¿sabes qué revisar?</h2>
        <p>
            Un terremoto puede dejar señales visibles de posible daño estructural.
            Saber qué observar — y cuándo evacuar — puede marcar la diferencia,
            pero también es fácil confundir grietas normales con daño estructural grave.
        </p>
    </div>
</section>

<!-- Propuesta de valor -->
<section class="section section--muted">
    <div class="container container--narrow">
        <h2>Lo que obtienes en esta Masterclass</h2>
        <p>
            Una sesión educativa de <?= (int) ($mc['duration_minutes'] / 60) ?> horas para aprender
            a evaluar visual y conceptualmente una estructura después de un sismo,
            identificar señales de posible daño y comprender qué debe revisar un profesional.
        </p>
    </div>
</section>

<!-- Para quién es -->
<section class="section">
    <div class="container container--narrow">
        <h2>Esta Masterclass es para ti, si…</h2>
        <ul class="check-list">
            <li>Quieres saber qué señales observar después de un sismo</li>
            <li>Necesitas entender cuándo es necesario evacuar</li>
            <li>Deseas comprender qué aspectos revisa un profesional estructural</li>
            <li>Tienes interés en seguridad de construcciones, sin ser necesariamente ingeniero</li>
        </ul>
    </div>
</section>

<!-- Qué aprenderás -->
<section class="section section--muted">
    <div class="container container--narrow">
        <h2>Qué aprenderás</h2>
        <ol class="numbered-list">
            <li>Evaluación visual y conceptual post-sismo</li>
            <li>Señales de posible daño estructural</li>
            <li>Criterios para decidir cuándo evacuar</li>
            <li>Aspectos que debe revisar un profesional competente</li>
        </ol>
    </div>
</section>

<!-- Instructor -->
<section class="section">
    <div class="container container--narrow">
        <h2>Instructor</h2>
        <h3><?= e($mc['instructor_name']) ?></h3>
        <p class="text-muted">Likantor — Ingeniería en Estructuras</p>
        <?php if (!empty($mc['instructor_bio'])): ?>
            <p><?= e($mc['instructor_bio']) ?></p>
        <?php else: ?>
            <p class="placeholder">[PLACEHOLDER — Biografía del instructor pendiente de confirmación por Likantor]</p>
        <?php endif; ?>
    </div>
</section>

<!-- Fecha / modalidad / precio -->
<section class="section section--accent" id="inscribirse">
    <div class="container container--narrow text-center">
        <h2>Inscripción</h2>
        <p class="price-tag"><?= e(number_format((float) $mc['price'], 2)) ?> <?= e($mc['currency']) ?></p>
        <p>Modalidad: Online mediante Zoom</p>
        <div class="hero__actions">
            <?php if ($isLoggedIn): ?>
                <a href="<?= e($enrollUrl) ?>" class="btn btn--primary btn--lg">Ir a pagar</a>
            <?php else: ?>
                <a href="<?= url('/registro') ?>" class="btn btn--primary btn--lg">Crear cuenta e inscribirme</a>
                <a href="<?= url('/login') ?>" class="btn btn--secondary">Ya tengo cuenta</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="section">
    <div class="container container--narrow">
        <h2>Preguntas frecuentes</h2>
        <details class="faq-item">
            <summary>¿Esta Masterclass sustituye una evaluación profesional?</summary>
            <p>No. Es contenido educativo. Ante daños o dudas sobre la seguridad de una construcción, consulte a un profesional competente.</p>
        </details>
        <details class="faq-item">
            <summary>¿Cómo accedo al evento?</summary>
            <p>Tras confirmar tu pago, recibirás acceso a tu área privada con el enlace de Zoom.</p>
        </details>
        <details class="faq-item">
            <summary>¿Habrá grabación?</summary>
            <p>Los participantes inscritos podrán acceder a materiales y grabación cuando estén disponibles.</p>
        </details>
    </div>
</section>

<!-- Formulario temario -->
<section class="section section--muted" id="temario">
    <div class="container container--narrow">
        <h2>Solicitar temario / información</h2>
        <form action="<?= url('/masterclasses/' . $mc['slug'] . '/lead') ?>" method="POST" class="form js-lead-form" novalidate>
            <?= csrf_field() ?>

            <!-- Honeypot anti-bot: campo oculto que un humano nunca completa -->
            <div class="hp-field" aria-hidden="true">
                <label for="mc-website">Sitio web (dejar vacío)</label>
                <input type="text" id="mc-website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <?php foreach (\App\Core\Utm::KEYS as $utmKey): ?>
                <input type="hidden" name="<?= e($utmKey) ?>" value="<?= e($utm[$utmKey] ?? '') ?>">
            <?php endforeach; ?>
            <input type="hidden" name="campaign" value="<?= e($utm['campaign'] ?? '') ?>">

            <div class="form-group">
                <label for="name">Nombre completo</label>
                <input type="text" id="name" name="name" required maxlength="150" value="<?= e(old('name')) ?>">
            </div>
            <div class="form-group">
                <label for="email">Correo electrónico</label>
                <input type="email" id="email" name="email" required maxlength="255" value="<?= e(old('email')) ?>">
            </div>
            <div class="form-group form-group--checkbox">
                <label>
                    <input type="checkbox" name="privacy" value="1" required>
                    Acepto el aviso de privacidad y autorizo el tratamiento de mis datos para recibir la información solicitada.
                    (<a href="<?= url('/aviso-de-privacidad') ?>" target="_blank">ver aviso completo</a>)
                </label>
            </div>
            <button type="submit" class="btn btn--primary">Enviar solicitud</button>
        </form>
    </div>
</section>

<!-- Disclaimer -->
<section class="section disclaimer">
    <div class="container container--narrow">
        <p><strong>Aviso importante:</strong> <?= e($mc['disclaimer_text'] ?? 'Esta Masterclass es contenido educativo y no sustituye una evaluación profesional de seguridad estructural.') ?></p>
    </div>
</section>

<!-- CTA final -->
<section class="section section--accent text-center">
    <div class="container">
        <h2>¿Listo para inscribirte?</h2>
        <a href="<?= url('/registro') ?>" class="btn btn--primary btn--lg">Inscribirme ahora</a>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof LikantorTimezone !== 'undefined') {
        LikantorTimezone.renderEventDateTime('#event-datetime');
    }
});
</script>
