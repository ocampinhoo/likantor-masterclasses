<?php

use App\Services\AuthService;

$mc = $masterclass;
$eventUtc = $mc['event_starts_at'];
$eventTz = $mc['timezone'] ?? 'America/Mexico_City';
$isLoggedIn = (new AuthService())->check();
$registerUrl = $isLoggedIn ? url('/checkout/' . $mc['slug']) : url('/registro');
$ctaLabel = $isLoggedIn ? 'Ir a pagar' : 'Quiero mi lugar';
$disclaimer = $mc['disclaimer_text'] ?? 'Esta Masterclass tiene fines educativos e informativos y no constituye una evaluación, dictamen, peritaje o diagnóstico estructural de una construcción específica.';
?>

<!-- 1. Hero -->
<section class="hero-landing" aria-labelledby="landing-title">
    <div class="container">
        <div class="hero-landing__badge">
            <span aria-hidden="true">●</span> En vivo · Zoom · 17 sep 2026
        </div>
        <h1 id="landing-title" class="hero-landing__title">Revisión de Estructuras Post-Sismo</h1>
        <p class="hero-landing__subtitle">
            Aprende a identificar señales que ameritan atención y comprende los aspectos básicos
            que deben revisarse después de un sismo — sin sustituir una evaluación profesional.
        </p>
        <div class="hero-landing__cta">
            <a href="<?= e($registerUrl) ?>" class="btn btn--primary btn--lg" id="inscribirse"><?= e($ctaLabel) ?></a>
        </div>
    </div>
</section>

<!-- 2. Fecha y hora -->
<section class="event-bar" aria-labelledby="event-info-title">
    <div class="container">
        <h2 id="event-info-title" class="sr-only">Información del evento</h2>
        <div class="event-bar__grid">
            <div class="event-bar__item">
                <span class="event-bar__label">Fecha</span>
                <span class="event-bar__value">17 de septiembre de 2026</span>
            </div>
            <div class="event-bar__item">
                <span class="event-bar__label">Hora (CDMX)</span>
                <span class="event-bar__value">6:30 PM</span>
            </div>
            <div class="event-bar__item">
                <span class="event-bar__label">Duración</span>
                <span class="event-bar__value">3 horas</span>
            </div>
            <div class="event-bar__item">
                <span class="event-bar__label">Modalidad</span>
                <span class="event-bar__value">En vivo por Zoom</span>
            </div>
        </div>

        <div class="timezone-panel" id="timezone-panel"
             data-event-utc="<?= e($eventUtc) ?>"
             data-event-timezone="<?= e($eventTz) ?>">
            <p class="timezone-panel__title">Horario en tu zona</p>
            <div class="timezone-panel__row">
                <div class="form-group" style="margin:0;">
                    <label for="tz-select">Selecciona zona horaria</label>
                    <select id="tz-select" aria-describedby="tz-help">
                        <option value="America/Mexico_City">Ciudad de México (CDMX)</option>
                        <option value="America/Tijuana">Tijuana</option>
                        <option value="America/Monterrey">Monterrey</option>
                        <option value="America/Cancun">Cancún</option>
                        <option value="America/Bogota">Bogotá</option>
                        <option value="America/Lima">Lima</option>
                        <option value="America/Santiago">Santiago</option>
                        <option value="America/Buenos_Aires">Buenos Aires</option>
                        <option value="America/New_York">Nueva York</option>
                        <option value="America/Los_Angeles">Los Ángeles</option>
                        <option value="Europe/Madrid">Madrid</option>
                    </select>
                </div>
                <div class="timezone-panel__display">
                    <p class="timezone-panel__display-label">Hora en zona seleccionada</p>
                    <p class="timezone-panel__display-value" id="tz-selected-display">—</p>
                </div>
                <div class="timezone-panel__display">
                    <p class="timezone-panel__display-label">Tu hora local (detectada)</p>
                    <p class="timezone-panel__display-value" id="tz-local-display">—</p>
                </div>
            </div>
            <p id="tz-help" class="text-muted" style="font-size:0.85rem;margin:0.75rem 0 0;">
                Hora base: America/Mexico_City · 17 sep 2026, 6:30 PM
            </p>
        </div>
    </div>
</section>

<!-- 3. Problema -->
<section class="section" aria-labelledby="problem-title">
    <div class="container container--narrow">
        <span class="section__eyebrow">El problema</span>
        <h2 id="problem-title" class="section__title">Después de un sismo, ¿qué observar?</h2>
        <p class="section__lead">
            Muchas personas no saben qué buscar ni cuándo actuar. Confundir un daño superficial
            con una señal estructural grave — o viceversa — puede poner en riesgo vidas.
        </p>
        <div class="problem-grid">
            <div class="problem-item">
                <span class="problem-item__icon" aria-hidden="true">01</span>
                <p><strong>Qué daños observar</strong> — grietas, deformaciones, desplomos parciales y otros indicios visibles.</p>
            </div>
            <div class="problem-item">
                <span class="problem-item__icon" aria-hidden="true">02</span>
                <p><strong>Señales preocupantes</strong> — indicadores que sugieren posible afectación estructural.</p>
            </div>
            <div class="problem-item">
                <span class="problem-item__icon" aria-hidden="true">03</span>
                <p><strong>Cuándo no ingresar</strong> — condiciones en las que evitar entrar a la edificación.</p>
            </div>
            <div class="problem-item">
                <span class="problem-item__icon" aria-hidden="true">04</span>
                <p><strong>Cuándo evacuar</strong> — criterios generales para decidir salir del inmueble.</p>
            </div>
            <div class="problem-item">
                <span class="problem-item__icon" aria-hidden="true">05</span>
                <p><strong>Evaluación profesional</strong> — cuándo es indispensable consultar a un ingeniero estructural competente.</p>
            </div>
            <div class="problem-item">
                <span class="problem-item__icon" aria-hidden="true">06</span>
                <p><strong>Daños superficiales vs. estructurales</strong> — diferencias conceptuales entre ambos tipos de señales.</p>
            </div>
        </div>
    </div>
</section>

<!-- 4. Para quién es -->
<section class="section section--muted" aria-labelledby="audience-title">
    <div class="container text-center">
        <span class="section__eyebrow">Audiencia</span>
        <h2 id="audience-title" class="section__title">¿Para quién es esta Masterclass?</h2>
        <p class="section__lead" style="margin-inline:auto;">
            Está dirigida a profesionales del sector y a cualquier persona interesada en
            seguridad estructural. No se requiere ser ingeniero para participar.
        </p>
        <div class="audience-pills" role="list">
            <span class="audience-pill" role="listitem">Estudiantes</span>
            <span class="audience-pill" role="listitem">Arquitectos</span>
            <span class="audience-pill" role="listitem">Ingenieros</span>
            <span class="audience-pill" role="listitem">Construcción</span>
            <span class="audience-pill" role="listitem">Propietarios</span>
            <span class="audience-pill" role="listitem">Público general</span>
        </div>
    </div>
</section>

<!-- 5. Qué aprenderás -->
<section class="section" aria-labelledby="modules-title">
    <div class="container">
        <span class="section__eyebrow">Contenido</span>
        <h2 id="modules-title" class="section__title">Qué aprenderás</h2>
        <p class="section__lead">
            Módulos orientativos — el temario definitivo será confirmado por Likantor.
        </p>
        <div class="modules-grid">
            <article class="module-card">
                <span class="module-card__num" aria-hidden="true">1</span>
                <h3 class="module-card__title">[PLACEHOLDER] Introducción post-sismo</h3>
                <p class="module-card__desc">Contexto general sobre el comportamiento de estructuras después de un evento sísmico.</p>
            </article>
            <article class="module-card">
                <span class="module-card__num" aria-hidden="true">2</span>
                <h3 class="module-card__title">[PLACEHOLDER] Inspección visual</h3>
                <p class="module-card__desc">Elementos y zonas prioritarias para una revisión visual inicial.</p>
            </article>
            <article class="module-card">
                <span class="module-card__num" aria-hidden="true">3</span>
                <h3 class="module-card__title">[PLACEHOLDER] Señales de alerta</h3>
                <p class="module-card__desc">Indicios que ameritan atención inmediata y precaución.</p>
            </article>
            <article class="module-card">
                <span class="module-card__num" aria-hidden="true">4</span>
                <h3 class="module-card__title">[PLACEHOLDER] Criterios de evacuación</h3>
                <p class="module-card__desc">Cuándo evacuar y qué factores considerar en la decisión.</p>
            </article>
            <article class="module-card">
                <span class="module-card__num" aria-hidden="true">5</span>
                <h3 class="module-card__title">[PLACEHOLDER] Rol del profesional</h3>
                <p class="module-card__desc">Qué evalúa un ingeniero estructural y cuándo es necesario contratarlo.</p>
            </article>
            <article class="module-card">
                <span class="module-card__num" aria-hidden="true">6</span>
                <h3 class="module-card__title">[PLACEHOLDER] Sesión de preguntas</h3>
                <p class="module-card__desc">Espacio para resolver dudas generales con el ponente.</p>
            </article>
        </div>
    </div>
</section>

<!-- 6. Qué incluye -->
<section class="section section--muted" aria-labelledby="includes-title">
    <div class="container container--narrow text-center">
        <span class="section__eyebrow">Incluye</span>
        <h2 id="includes-title" class="section__title">Qué incluye tu inscripción</h2>
        <div class="includes-list">
            <div class="includes-item">
                <span class="includes-item__check" aria-hidden="true">✓</span>
                <span>Masterclass de 3 horas en vivo</span>
            </div>
            <div class="includes-item">
                <span class="includes-item__check" aria-hidden="true">✓</span>
                <span>Transmisión en vivo vía Zoom</span>
            </div>
            <div class="includes-item">
                <span class="includes-item__check" aria-hidden="true">✓</span>
                <span>Sesión de preguntas y respuestas</span>
            </div>
            <div class="includes-item">
                <span class="includes-item__check" aria-hidden="true">✓</span>
                <span>Acceso mediante plataforma privada</span>
            </div>
            <div class="includes-item includes-item--pending">
                <span class="includes-item__check" aria-hidden="true">○</span>
                <span>Materiales digitales <span class="includes-item__pending">(sujeto a confirmación)</span></span>
            </div>
            <div class="includes-item includes-item--pending">
                <span class="includes-item__check" aria-hidden="true">○</span>
                <span>Grabación de la sesión <span class="includes-item__pending">(sujeto a confirmación)</span></span>
            </div>
        </div>
    </div>
</section>

<!-- 7. Instructor -->
<section class="section" aria-labelledby="instructor-title">
    <div class="container container--narrow">
        <span class="section__eyebrow">Instructor</span>
        <h2 id="instructor-title" class="section__title">Fernando Robledo</h2>
        <div class="instructor-card">
            <div class="instructor-card__avatar" aria-hidden="true">FR</div>
            <div>
                <h3 class="instructor-card__name">Fernando Robledo</h3>
                <p class="instructor-card__role">Likantor — Ingeniería en Estructuras</p>
                <p>
                    Ponente de esta Masterclass, bajo la firma Likantor — Ingeniería en Estructuras,
                    con base en Guadalajara, Jalisco, México.
                </p>
                <p class="placeholder">
                    [PLACEHOLDER — Biografía, formación y trayectoria profesional de Fernando Robledo
                    pendiente de confirmación por Likantor.]
                </p>
            </div>
        </div>
    </div>
</section>

<!-- 8. Precio -->
<section class="section section--muted" aria-labelledby="pricing-title">
    <div class="container">
        <div class="pricing-card">
            <p class="pricing-card__label">Acceso a la Masterclass</p>
            <p class="pricing-card__price">
                <?= e(number_format((float) ($mc['price'] ?? 65), 0)) ?>
                <span class="pricing-card__currency"><?= e($mc['currency'] ?? 'USD') ?></span>
            </p>
            <p class="pricing-card__note">Pago seguro · Confirmación por plataforma</p>
            <a href="<?= e($registerUrl) ?>" class="btn btn--primary btn--lg btn--block"><?= $isLoggedIn ? 'Ir a pagar' : 'Reservar mi lugar' ?></a>
        </div>
    </div>
</section>

<!-- 9. FAQ -->
<section class="section" aria-labelledby="faq-title">
    <div class="container container--narrow">
        <span class="section__eyebrow">FAQ</span>
        <h2 id="faq-title" class="section__title">Preguntas frecuentes</h2>
        <div class="faq-list">
            <details class="faq-item">
                <summary>¿Cuándo es la Masterclass?</summary>
                <div class="faq-item__content">17 de septiembre de 2026, a las 6:30 PM horario de Ciudad de México (America/Mexico_City).</div>
            </details>
            <details class="faq-item">
                <summary>¿Cuánto dura?</summary>
                <div class="faq-item__content">3 horas, incluyendo sesión de preguntas.</div>
            </details>
            <details class="faq-item">
                <summary>¿Cómo se imparte?</summary>
                <div class="faq-item__content">En línea, en vivo, a través de Zoom. Recibirás el acceso en tu área privada tras confirmar el pago.</div>
            </details>
            <details class="faq-item">
                <summary>¿Qué necesito para participar?</summary>
                <div class="faq-item__content">Computadora o dispositivo con internet, navegador actualizado y la aplicación o enlace de Zoom.</div>
            </details>
            <details class="faq-item">
                <summary>¿Cómo accedo después de pagar?</summary>
                <div class="faq-item__content">Tras la confirmación del pago (vía webhook), se activará tu acceso en la plataforma privada con el enlace de Zoom.</div>
            </details>
            <details class="faq-item">
                <summary>¿Qué métodos de pago aceptan?</summary>
                <div class="faq-item__content">Mercado Pago y Stripe. El pago se procesa de forma segura en la plataforma del proveedor; no almacenamos datos de tarjeta.</div>
            </details>
            <details class="faq-item">
                <summary>¿En qué idioma se imparte?</summary>
                <div class="faq-item__content">Español.</div>
            </details>
            <details class="faq-item">
                <summary>¿Habrá grabación?</summary>
                <div class="faq-item__content">La inclusión de grabación está sujeta a confirmación. Si se confirma, estará disponible en tu área privada.</div>
            </details>
            <details class="faq-item">
                <summary>¿Puedo solicitar reembolso?</summary>
                <div class="faq-item__content">Las políticas de devolución se publicarán en los Términos y Condiciones. [PLACEHOLDER — Política de reembolsos pendiente de definir.]</div>
            </details>
            <details class="faq-item">
                <summary>¿Esto sustituye una evaluación estructural profesional?</summary>
                <div class="faq-item__content"><strong>No.</strong> Es contenido educativo. Ante daños o dudas sobre estabilidad, consulte a un profesional competente y evite ingresar a la construcción.</div>
            </details>
        </div>
    </div>
</section>

<!-- 10. Disclaimer -->
<section class="section section--muted" aria-labelledby="disclaimer-title">
    <div class="container container--narrow">
        <h2 id="disclaimer-title" class="sr-only">Aviso importante</h2>
        <div class="disclaimer-box" role="note">
            <p><strong>Aviso importante:</strong> <?= e($disclaimer) ?></p>
        </div>
    </div>
</section>

<!-- Formulario temario -->
<section class="section lead-section" id="temario" aria-labelledby="lead-title">
    <div class="container container--narrow">
        <span class="section__eyebrow">Información</span>
        <h2 id="lead-title" class="section__title">Solicitar temario / más información</h2>
        <form action="<?= url('/masterclass/revision-estructuras-post-sismo/lead') ?>" method="POST" class="form js-lead-form" novalidate>
            <?= csrf_field() ?>

            <!-- Honeypot anti-bot: campo oculto que un humano nunca completa -->
            <div class="hp-field" aria-hidden="true">
                <label for="lead-website">Sitio web (dejar vacío)</label>
                <input type="text" id="lead-website" name="website" tabindex="-1" autocomplete="off">
            </div>

            <?php foreach (\App\Core\Utm::KEYS as $utmKey): ?>
                <input type="hidden" name="<?= e($utmKey) ?>" value="<?= e($utm[$utmKey] ?? '') ?>">
            <?php endforeach; ?>
            <input type="hidden" name="campaign" value="<?= e($utm['campaign'] ?? '') ?>">

            <div class="form-group">
                <label for="lead-name">Nombre completo</label>
                <input type="text" id="lead-name" name="name" required maxlength="150" autocomplete="name" value="<?= e(old('name')) ?>">
            </div>
            <div class="form-group">
                <label for="lead-email">Correo electrónico</label>
                <input type="email" id="lead-email" name="email" required maxlength="255" autocomplete="email" value="<?= e(old('email')) ?>">
            </div>
            <div class="form-group form-group--checkbox">
                <label>
                    <input type="checkbox" name="privacy" value="1" required>
                    Acepto el aviso de privacidad y autorizo el tratamiento de mis datos para recibir la información solicitada.
                    (<a href="<?= url('/aviso-de-privacidad') ?>" target="_blank" rel="noopener">ver aviso completo</a>)
                </label>
            </div>
            <button type="submit" class="btn btn--primary btn--block">Enviar solicitud</button>
        </form>
    </div>
</section>

<!-- 11. CTA Final -->
<section class="final-cta" aria-labelledby="final-cta-title">
    <div class="container">
        <h2 id="final-cta-title" class="final-cta__title">Reserva tu lugar</h2>
        <p class="final-cta__text">
            17 de septiembre de 2026 · 6:30 PM CDMX · 3 horas · 65 USD · En vivo por Zoom
        </p>
        <a href="<?= e($registerUrl) ?>" class="btn btn--primary btn--lg"><?= $isLoggedIn ? 'Ir a pagar' : 'Reservar mi lugar' ?></a>
    </div>
</section>
